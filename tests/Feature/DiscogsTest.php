<?php

use App\Models\Artist;
use App\Models\Edition;
use App\Models\Label;
use App\Models\PriceHistory;
use App\Models\Record;
use App\Services\Discogs\DiscogsClient;
use App\Support\CatchUpSchedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['services.discogs.token' => 'test-token']);
    app()->forgetInstance(DiscogsClient::class);
    Storage::fake('local');
});

function discogsImage(): string
{
    $image = imagecreatetruecolor(20, 20);
    ob_start();
    imagejpeg($image);

    return ob_get_clean();
}

function discogsRelease(array $overrides = []): array
{
    return array_replace([
        'id' => 1234,
        'title' => 'Computer World',
        'year' => 2009,
        'country' => 'Germany',
        'master_id' => 77,
        'artists' => [['name' => 'Kraftwerk (2)', 'anv' => '', 'join' => '']],
        'labels' => [['name' => 'Kling Klang (3)', 'catno' => '50999 9 66023 1 9']],
        'formats' => [['name' => 'Vinyl', 'qty' => '1', 'descriptions' => ['LP', 'Album', 'Reissue', 'Remastered', 'Limited Edition'], 'text' => 'Red Translucent']],
        'identifiers' => [
            ['type' => 'Barcode', 'value' => '5099996602319'],
            ['type' => 'Matrix / Runout', 'value' => 'KW 4-A'],
            ['type' => 'Matrix / Runout', 'value' => 'KW 4-B'],
        ],
        'images' => [['type' => 'primary', 'uri' => 'https://i.discogs.com/cover.jpg', 'uri150' => 'https://i.discogs.com/cover-150.jpg']],
    ], $overrides);
}

function fakeDiscogs(array $extra = []): void
{
    Http::fake(array_merge($extra, [
        'api.discogs.com/database/search*' => Http::response(['results' => [[
            'id' => 1234, 'title' => 'Kraftwerk - Computer World', 'year' => '2009', 'country' => 'Germany',
            'format' => ['Vinyl', 'LP', 'Vinyl'], 'label' => ['Kling Klang', 'EMI'], 'catno' => '50999 9 66023 1 9',
            'thumb' => 'https://i.discogs.com/thumb.jpg',
        ]]]),
        'api.discogs.com/releases/1234' => Http::response(discogsRelease()),
        'api.discogs.com/masters/77' => Http::response(['id' => 77, 'year' => 1981]),
        'api.discogs.com/marketplace/price_suggestions/1234' => Http::response([
            'Near Mint (NM or M-)' => ['currency' => 'EUR', 'value' => 30.0],
            'Very Good Plus (VG+)' => ['currency' => 'EUR', 'value' => 21.456],
            'Very Good (VG)' => ['currency' => 'EUR', 'value' => 12.0],
        ]),
        'api.discogs.com/marketplace/stats/1234' => Http::response(['lowest_price' => ['currency' => 'EUR', 'value' => 18.5], 'num_for_sale' => 7]),
        'https://i.discogs.com/*' => Http::response(discogsImage(), 200, ['Content-Type' => 'image/jpeg']),
    ]));
}

function discogsRecord(array $attributes = []): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = 'Computer World';
    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }
    $record->save();

    return $record;
}

test('releases can be searched', function () {
    fakeDiscogs();

    $this->getJson(route('discogs.search', ['barcode' => '5099996602319']))
        ->assertOk()
        ->assertJsonPath('results.0.id', 1234)
        ->assertJsonPath('results.0.format', 'Vinyl, LP')
        ->assertJsonPath('results.0.url', 'https://www.discogs.com/release/1234');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'barcode=5099996602319')
        && str_contains($request->url(), 'type=release')
        && $request->header('Authorization')[0] === 'Discogs token=test-token'
        && str_starts_with($request->header('User-Agent')[0], 'RecordLoom'));
});

test('a release is translated into form data', function () {
    fakeDiscogs();

    $this->getJson(route('discogs.release', 1234))
        ->assertOk()
        ->assertJson([
            'discogs_release_id' => 1234,
            'kind' => 'LP',
            'artist_name' => 'Kraftwerk',
            'title' => 'Computer World',
            'label_name' => 'Kling Klang',
            'catalog_number' => '50999 9 66023 1 9',
            'barcode' => '5099996602319',
            'matrix_number' => 'KW 4-A / KW 4-B',
            'country_name' => 'Germany',
            'release_year' => 1981,
            'reissue_year' => 2009,
            'cover_url' => 'https://i.discogs.com/cover.jpg',
        ])
        ->assertJsonPath('editions', Edition::whereIn('name', ['Limitierte Auflage', 'Farbiges Vinyl'])->orderBy('id')->pluck('id')->all());
});

test('several artists and cds are mapped', function () {
    fakeDiscogs(['api.discogs.com/releases/99' => Http::response(discogsRelease([
        'id' => 99, 'master_id' => null, 'year' => 1995,
        'artists' => [['name' => 'Moebius', 'anv' => '', 'join' => '&'], ['name' => 'Plank (2)', 'anv' => 'Conny Plank', 'join' => '']],
        'formats' => [['name' => 'CD', 'descriptions' => ['Album']]],
    ]))]);

    $this->getJson(route('discogs.release', 99))
        ->assertJson(['artist_name' => 'Moebius & Conny Plank', 'kind' => 'CD', 'release_year' => 1995])
        ->assertJsonMissing(['reissue_year' => 1995]);
});

test('errors of discogs are shown', function () {
    Http::fake(['api.discogs.com/*' => Http::response(['message' => 'You are making requests too quickly.'], 429)]);

    $this->getJson(route('discogs.search', ['q' => 'kraftwerk']))
        ->assertStatus(502)
        ->assertJson(['error' => 'Zu viele Anfragen an Discogs. Bitte eine Minute warten.']);
});

test('without token discogs is not used', function () {
    config(['services.discogs.token' => null]);
    app()->forgetInstance(DiscogsClient::class);
    Http::fake();

    $this->getJson(route('discogs.search', ['q' => 'kraftwerk']))->assertStatus(502);
    $this->get(route('records.create'))->assertSee('Discogs ist nicht eingerichtet');
    Http::assertNothingSent();
});

test('a record can be saved with the release and the cover from discogs', function () {
    fakeDiscogs();

    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Computer World', 'label_name' => 'Kling Klang',
        'discogs_release_id' => 1234, 'discogs_cover_url' => 'https://i.discogs.com/cover.jpg',
    ])->assertRedirect()->assertSessionMissing('warning.0');

    $record = Record::firstWhere('title', 'Computer World');
    expect($record->discogs_release_id)->toBe(1234)
        ->and($record->cover_path)->not->toBeNull();
    Storage::disk('local')->assertExists($record->cover_path);

    $this->get(route('records.show', $record))->assertSee('https://www.discogs.com/release/1234');
});

test('covers are only downloaded from discogs', function () {
    fakeDiscogs(['evil.example/*' => Http::response('secret')]);

    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Evil', 'label_name' => 'Kling Klang',
        'discogs_cover_url' => 'https://evil.example/internal',
    ])->assertSessionHas('warning', 'Das Bild stammt nicht von Discogs.');

    expect(Record::firstWhere('title', 'Evil')->cover_path)->toBeNull();
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'evil.example'));
});

test('market data can be loaded and both prices can be used', function () {
    fakeDiscogs();
    $record = discogsRecord(['discogs_release_id' => 1234, 'grading_media' => 70]);

    $this->post(route('discogs.prices', $record))->assertSessionHas('info')->assertSessionMissing('warning');
    $record->refresh();
    expect($record->discogs_lowest_price)->toBe('18.50')
        ->and($record->discogs_num_for_sale)->toBe(7)
        ->and($record->discogsSuggestedPrice()['value'])->toBe(21.456)
        ->and($record->discogs_suggestions_note)->toBeNull();

    $this->get(route('records.show', $record))
        ->assertSee('18,50 €')->assertSee('7 Angebote')
        ->assertSee('12,00 € – 30,00 €')
        ->assertSee('21,46 €');

    $this->post(route('discogs.apply-price', $record), ['source' => 'suggestion'])->assertSessionHas('info');
    expect($record->fresh()->current_price)->toBe('21.46');

    $this->post(route('discogs.apply-price', $record), ['source' => 'lowest'])->assertSessionHas('info');
    expect($record->fresh()->current_price)->toBe('18.50');

    $history = PriceHistory::where('record_id', $record->id)->orderBy('id')->get();
    expect($history->pluck('price')->all())->toBe(['21.46', '18.50'])
        ->and($history->last()->platform->name)->toBe('Discogs')
        ->and($record->fresh()->platform->name)->toBe('Discogs');

    $this->get(route('records.show', $record))->assertSee('Preisentwicklung');
});

test('missing price suggestions are explained instead of reported as error', function () {
    fakeDiscogs(['api.discogs.com/marketplace/price_suggestions/*' => Http::response(['message' => 'The requested resource was not found.'], 404)]);
    $record = discogsRecord(['discogs_release_id' => 1234, 'grading_media' => 70]);

    $this->post(route('discogs.prices', $record))
        ->assertSessionHas('info')
        ->assertSessionMissing('warning')
        ->assertSessionMissing('error');

    $record->refresh();
    expect($record->discogs_num_for_sale)->toBe(7)
        ->and($record->discogs_suggestions_note)->toContain('Verkäufer-Einstellungen');

    $this->get(route('records.show', $record))->assertSee('Verkäufer-Einstellungen');
    $this->post(route('discogs.apply-price', $record), ['source' => 'suggestion'])->assertSessionHas('error');
    $this->post(route('discogs.apply-price', $record), ['source' => 'lowest'])->assertSessionHas('info');
});

test('the lowest offer can not be used when nothing is offered', function () {
    fakeDiscogs(['api.discogs.com/marketplace/stats/*' => Http::response(['lowest_price' => null, 'num_for_sale' => 0])]);
    $record = discogsRecord(['discogs_release_id' => 1234]);

    $this->post(route('discogs.prices', $record));
    $this->get(route('records.show', $record))->assertSee('Zurzeit keine Angebote.');
    $this->post(route('discogs.apply-price', $record), ['source' => 'lowest'])->assertSessionHas('error');
});

test('changing the release clears the market data', function () {
    $record = discogsRecord(['discogs_release_id' => 1234, 'discogs_lowest_price' => 10, 'discogs_prices_updated_at' => now()]);

    $this->put(route('records.update', $record), [
        'kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Computer World', 'label_name' => 'Kling Klang', 'discogs_release_id' => 555,
    ]);

    $record->refresh();
    expect($record->discogs_release_id)->toBe(555)->and($record->discogs_lowest_price)->toBeNull();
});

test('existing records are compared before linking', function () {
    fakeDiscogs();
    $record = discogsRecord(['barcode' => '5099996602319', 'catalog_number' => 'EIGENE-NR', 'release_year' => 1990]);

    $this->get(route('discogs.match'))->assertOk()->assertSee(route('discogs.review', $record), false);
    $this->getJson(route('discogs.suggestions', $record))->assertJsonPath('results.0.id', 1234);

    $this->get(route('discogs.review', ['record' => $record, 'release_id' => 1234]))
        ->assertOk()
        ->assertSee('EIGENE-NR')->assertSee('50999 9 66023 1 9')
        ->assertSee('1990')->assertSee('1981')
        ->assertSee('Limitierte Auflage');

    // Keep the own catalog number, take the year from Discogs, fill empty fields, add one edition and the cover.
    $limited = Edition::firstWhere('name', 'Limitierte Auflage');
    $this->post(route('discogs.link', $record), [
        'release_id' => 1234,
        'use' => ['catalog_number' => 'keep', 'release_year' => 'discogs', 'matrix_number' => 'discogs', 'country_name' => 'discogs', 'title' => 'keep'],
        'editions' => [$limited->id],
        'cover' => 'discogs',
    ])->assertRedirect(route('discogs.match'))->assertSessionHas('info');

    $record->refresh();
    expect($record->discogs_release_id)->toBe(1234)
        ->and($record->catalog_number)->toBe('EIGENE-NR')
        ->and($record->release_year)->toBe(1981)
        ->and($record->matrix_number)->toBe('KW 4-A / KW 4-B')
        ->and($record->country->name)->toBe('Germany')
        ->and($record->reissue_year)->toBeNull()
        ->and($record->editions->pluck('name')->all())->toBe(['Limitierte Auflage'])
        ->and($record->cover_path)->not->toBeNull();

    $this->get(route('discogs.match'))->assertDontSee(route('discogs.review', $record), false);
});

test('without choices nothing is overwritten', function () {
    fakeDiscogs();
    $record = discogsRecord(['title' => 'Mein Titel', 'catalog_number' => 'EIGENE-NR']);

    $this->post(route('discogs.link', $record), ['release_id' => 1234])->assertSessionHas('info');

    $record->refresh();
    expect($record->title)->toBe('Mein Titel')
        ->and($record->catalog_number)->toBe('EIGENE-NR')
        ->and($record->barcode)->toBeNull()
        ->and($record->cover_path)->toBeNull()
        ->and($record->discogs_release_id)->toBe(1234);
});

test('records can be skipped and unlinked', function () {
    $record = discogsRecord();

    $this->post(route('discogs.ignore', $record));
    $this->get(route('discogs.match'))->assertDontSee(route('discogs.suggestions', $record));
    $this->get(route('discogs.match', ['ignored' => 1]))->assertSee(route('discogs.suggestions', $record));

    $record->discogs_release_id = 1234;
    $record->save();
    $this->delete(route('discogs.unlink', $record));
    expect($record->fresh()->discogs_release_id)->toBeNull();
});

test('the nightly command updates records marked for sale', function () {
    fakeDiscogs();
    $selling = discogsRecord(['discogs_release_id' => 1234, 'selling' => true]);
    $notSelling = discogsRecord(['title' => 'Behalten', 'discogs_release_id' => 1234]);

    $this->artisan('discogs:update-prices')->assertSuccessful();

    expect($selling->fresh()->discogs_prices_updated_at)->not->toBeNull()
        ->and($notSelling->fresh()->discogs_prices_updated_at)->toBeNull();

    $this->artisan('discogs:update-prices --all')->assertSuccessful();
    expect($notSelling->fresh()->discogs_prices_updated_at)->not->toBeNull();
});

test('the discogs id is part of the csv export', function () {
    discogsRecord(['discogs_release_id' => 1234]);

    expect($this->get(route('records.export'))->streamedContent())->toContain('Discogs-ID')->toContain(';1234');
});

test('the record form offers the barcode scanner', function () {
    $this->get(route('records.create'))
        ->assertSee('data-barcode-scan="discogs"', false)
        ->assertSee('capture="environment"', false)
        ->assertSee('Barcode scannen');
});

test('the market data update catches up missed days', function () {
    fakeDiscogs();
    $record = discogsRecord(['discogs_release_id' => 1234, 'selling' => true]);
    $this->travelTo(now()->startOfDay());

    $this->artisan('discogs:update-prices --if-due')->assertSuccessful();
    $first = $record->fresh()->discogs_prices_updated_at;

    $this->travel(2)->hours();
    $this->artisan('discogs:update-prices --if-due');
    expect($record->fresh()->discogs_prices_updated_at->equalTo($first))->toBeTrue();

    // Server was off for three days: runs as soon as the scheduler runs again.
    $this->travel(3)->days();
    $this->artisan('discogs:update-prices --if-due');
    expect($record->fresh()->discogs_prices_updated_at->greaterThan($first))->toBeTrue();
});

test('a last run in the future (wrong clock) counts as due', function () {
    $schedule = app(CatchUpSchedule::class);
    $this->travelTo(now()->addYear());
    $schedule->markRun('test');
    $this->travelBack();

    expect($schedule->isDue('test', '7 days'))->toBeTrue();
});

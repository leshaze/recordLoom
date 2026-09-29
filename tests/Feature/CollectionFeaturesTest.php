<?php

use App\Models\Artist;
use App\Models\Edition;
use App\Models\Label;
use App\Models\Platform;
use App\Models\Record;
use App\Services\Discogs\DiscogsClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function newRecord(array $attributes = []): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => $attributes['artist'] ?? 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => $attributes['label'] ?? 'Kling Klang'])->id;
    unset($attributes['artist'], $attributes['label']);
    $record->title = 'Autobahn';
    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }
    $record->save();

    return $record;
}

test('artists and labels can be searched, filtered and sorted', function () {
    newRecord(['title' => 'Autobahn', 'current_price' => '30']);
    newRecord(['title' => 'Radio-Aktivität', 'current_price' => '20']);
    newRecord(['title' => 'Tour de France', 'kind' => 'CD', 'current_price' => '12.5']);
    newRecord(['title' => 'Unknown Pleasures', 'artist' => 'Joy Division', 'label' => 'Factory', 'current_price' => '100']);
    newRecord(['title' => 'Mensch-Maschine', 'current_price' => '1000', 'sold' => true]);
    Artist::forceCreate(['name' => 'Amon Düül', 'description' => 'Krautrock aus München']);
    Label::create(['name' => 'Brain']);

    // Name first by default, totals calculated per kind.
    $this->get(route('artists.index'))
        ->assertSeeInOrder(['Amon Düül', 'Joy Division', 'Kraftwerk'])
        ->assertSeeInOrder(['Kraftwerk', '3', '1', '50,00 €', '12,50 €']);

    // Sold records are counted, but not part of the value.
    $this->get(route('artists.show', Artist::firstWhere('name', 'Kraftwerk')))->assertSee('62,50 €')->assertDontSee('1.062,50 €');
    $this->get(route('labels.show', Label::firstWhere('name', 'Kling Klang')))->assertSee('62,50 €')->assertDontSee('1.062,50 €');

    $this->get(route('artists.index', ['q' => 'krautrock']))->assertSee('Amon Düül')->assertDontSee('Joy Division');
    $this->get(route('artists.index', ['records' => 'without']))->assertSee('Amon Düül')->assertDontSee('Kraftwerk');
    $this->get(route('artists.index', ['records' => 'with']))->assertSee('Kraftwerk')->assertDontSee('Amon Düül');

    // Numbers are sorted, entries without records stay at the end in both directions.
    $this->get(route('artists.index', ['sort' => 'lp_value', 'dir' => 'desc']))->assertSeeInOrder(['Joy Division', 'Kraftwerk', 'Amon Düül']);
    $this->get(route('artists.index', ['sort' => 'lp_value', 'dir' => 'asc']))->assertSeeInOrder(['Kraftwerk', 'Joy Division', 'Amon Düül']);
    $this->get(route('artists.index', ['sort' => 'lps', 'dir' => 'desc']))->assertSeeInOrder(['Kraftwerk', 'Joy Division', 'Amon Düül']);

    $this->get(route('labels.index', ['sort' => 'cds', 'dir' => 'desc']))->assertSeeInOrder(['Kling Klang', 'Brain']);
    $this->get(route('labels.index', ['records' => 'without']))->assertSee('Brain')->assertDontSee('Factory');
    $this->get(route('labels.index', ['q' => 'fact', 'per_page' => 30]))->assertSee('Factory')->assertDontSee('Kling Klang');

    // Invalid values fall back to the defaults.
    $this->get(route('labels.index', ['sort' => 'id; drop', 'dir' => 'x', 'records' => 'x', 'per_page' => 5]))->assertOk();
});

test('platforms can be searched, filtered and sorted like artists and labels', function () {
    $shop = Platform::create(['name' => 'Plattenladen']);
    $discogs = Platform::forceCreate(['name' => 'Discogs', 'url' => 'https://www.discogs.com']);
    Platform::forceCreate(['name' => 'Flohmarkt', 'url' => 'javascript:alert(1)']);
    newRecord(['title' => 'Autobahn', 'current_price' => '30', 'platform_id' => $shop->id]);
    newRecord(['title' => 'Radio-Aktivität', 'current_price' => '20', 'platform_id' => $discogs->id]);
    newRecord(['title' => 'Mensch-Maschine', 'current_price' => '1000', 'sold' => true, 'platform_id' => $discogs->id]);

    $this->get(route('platforms.index'))
        ->assertSeeInOrder(['Discogs', 'Flohmarkt', 'Plattenladen'])
        ->assertSee('href="https://www.discogs.com"', false)
        ->assertDontSee('href="javascript:alert(1)"', false)
        ->assertDontSee('1.020,00 €');

    $this->get(route('platforms.index', ['sort' => 'lp_value', 'dir' => 'desc']))->assertSeeInOrder(['Plattenladen', 'Discogs', 'Flohmarkt']);
    $this->get(route('platforms.index', ['q' => 'discogs.com']))->assertSee('Discogs')->assertDontSee('Plattenladen');
    $this->get(route('platforms.index', ['records' => 'without']))->assertSee('Flohmarkt')->assertDontSee('Plattenladen');

    // Sold records are not part of the value on the detail page either.
    $this->get(route('platforms.show', $discogs))->assertSee('20,00 €')->assertDontSee('1.020,00 €');
});

test('the record list can be searched, filtered and sorted', function () {
    newRecord(['title' => 'Autobahn', 'release_year' => 1974, 'current_price' => '30']);
    newRecord(['title' => 'Computerwelt', 'kind' => 'CD', 'release_year' => 1981, 'current_price' => '5']);
    newRecord(['title' => 'Unknown Pleasures', 'artist' => 'Joy Division', 'label' => 'Factory', 'selling' => true, 'current_price' => '100']);
    newRecord(['title' => 'Closer', 'artist' => 'Joy Division', 'label' => 'Factory', 'sold' => true]);

    $this->get(route('records.index', ['q' => 'joy']))->assertSee('Unknown Pleasures')->assertDontSee('Autobahn');
    $this->get(route('records.index', ['kind' => 'CD']))->assertSee('Computerwelt')->assertDontSee('Autobahn');
    $this->get(route('records.index', ['status' => 'selling']))->assertSee('Unknown Pleasures')->assertDontSee('Closer');
    $this->get(route('records.index', ['status' => 'sold']))->assertSee('Closer')->assertDontSee('Autobahn');
    $this->get(route('records.index', ['label' => Label::firstWhere('name', 'Factory')->id]))->assertSee('Closer')->assertDontSee('Computerwelt');

    $this->get(route('records.index', ['sort' => 'price', 'dir' => 'desc']))
        ->assertSeeInOrder(['Unknown Pleasures', 'Autobahn', 'Computerwelt']);
    $this->get(route('records.index', ['sort' => 'year', 'dir' => 'asc', 'kind' => 'LP', 'q' => 'a']))
        ->assertSeeInOrder(['Autobahn']);
    $this->get(route('records.index', ['sort' => 'artist']))->assertSeeInOrder(['Joy Division', 'Kraftwerk']);

    // Invalid parameters are ignored.
    $this->get(route('records.index', ['sort' => 'drop table', 'per_page' => 5000, 'status' => 'x']))->assertOk();
});

test('gradings are shown as readable badges', function () {
    $record = newRecord(['grading_media' => 70, 'grading_cover' => 100]);

    $this->get(route('records.index'))->assertSee('VG++')->assertSee('M-');
    $this->get(route('records.show', $record))->assertSee('70% - GER: VG++ / US: VG+');
});

test('covers can be uploaded, shown, replaced and removed', function () {
    Storage::fake('local');

    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Can', 'title' => 'Tago Mago', 'label_name' => 'United Artists',
        'cover' => UploadedFile::fake()->image('cover.jpg', 800, 800),
    ])->assertRedirect();

    $record = Record::firstWhere('title', 'Tago Mago');
    expect($record->cover_path)->not->toBeNull();
    Storage::disk('local')->assertExists($record->cover_path);

    $this->get(route('records.cover', $record))->assertOk();
    $this->get(route('records.cover', ['record' => $record, 'thumb' => 1]))->assertOk();
    $this->get(route('records.show', $record))->assertSee(route('records.cover', ['record' => $record, 'v' => $record->updated_at->timestamp]), false);

    $oldPath = $record->cover_path;
    $this->put(route('records.update', $record), [
        'kind' => 'LP', 'artist_name' => 'Can', 'title' => 'Tago Mago', 'label_name' => 'United Artists',
        'cover' => UploadedFile::fake()->image('new.png'),
    ])->assertRedirect();
    Storage::disk('local')->assertMissing($oldPath);

    $this->put(route('records.update', $record), [
        'kind' => 'LP', 'artist_name' => 'Can', 'title' => 'Tago Mago', 'label_name' => 'United Artists', 'remove_cover' => '1',
    ])->assertRedirect();
    expect($record->fresh()->cover_path)->toBeNull();
    $this->get(route('records.cover', $record))->assertNotFound();
});

test('only images are accepted as cover', function () {
    Storage::fake('local');

    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Can', 'title' => 'Evil', 'label_name' => 'X',
        'cover' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
    ])->assertSessionHasErrors('cover');

    expect(Record::count())->toBe(0);
});

test('deleting a record deletes its cover', function () {
    Storage::fake('local');
    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Can', 'title' => 'Future Days', 'label_name' => 'UA',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]);
    $record = Record::firstWhere('title', 'Future Days');
    $path = $record->cover_path;

    $this->delete(route('records.destroy', $record))->assertRedirect(route('records.index'));

    Storage::disk('local')->assertMissing($path);
});

test('editions can be managed and assigned to records', function () {
    $this->post(route('editions.store'), ['name' => 'Promo'])->assertRedirect(route('editions.index'));
    $this->post(route('editions.store'), ['name' => 'Promo'])->assertSessionHasErrors('name');
    $promo = Edition::firstWhere('name', 'Promo');
    $box = Edition::create(['name' => 'Boxset XL']);

    $this->post(route('records.store'), [
        'kind' => 'LP', 'artist_name' => 'Neu!', 'title' => 'Neu! 2', 'label_name' => 'Brain',
        'editions' => [$promo->id, $box->id],
    ])->assertRedirect();
    $record = Record::firstWhere('title', 'Neu! 2');
    expect($record->editions->pluck('name')->all())->toBe(['Boxset XL', 'Promo']);

    $this->get(route('records.index', ['edition' => $promo->id]))->assertSee('Neu! 2');
    $this->get(route('records.show', $record))->assertSee('Boxset XL');

    $this->put(route('editions.update', $promo), ['name' => 'Promo-Pressung'])->assertRedirect();
    expect($promo->fresh()->name)->toBe('Promo-Pressung');

    // Unchecking all editions removes them from the record.
    $this->put(route('records.update', $record), ['kind' => 'LP', 'artist_name' => 'Neu!', 'title' => 'Neu! 2', 'label_name' => 'Brain'])->assertRedirect();
    expect($record->editions()->count())->toBe(0);

    $record->editions()->attach($box);
    $this->delete(route('editions.destroy', $box))->assertRedirect();
    expect(Record::count())->toBe(1)->and($record->editions()->count())->toBe(0);
});

test('years and the sale date are validated', function () {
    $record = newRecord();

    $this->put(route('records.update', $record), [
        'kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Autobahn', 'label_name' => 'Kling Klang',
        'release_year' => 'neunzehnhundert', 'sold_on' => 'gestern',
    ])->assertSessionHasErrors(['release_year', 'sold_on']);

    $this->put(route('records.update', $record), [
        'kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Autobahn', 'label_name' => 'Kling Klang',
        'release_year' => '1974', 'sold' => '1', 'sold_on' => '2025-05-01',
    ])->assertSessionHasNoErrors();

    $record->refresh();
    expect($record->release_year)->toBe(1974)->and($record->sold_on->format('d.m.Y'))->toBe('01.05.2025');
});

test('records can be exported as csv', function () {
    $record = newRecord(['title' => '=HYPERLINK("http://evil")', 'current_price' => '12.5', 'release_year' => 1974, 'selling' => true]);
    $record->editions()->attach(Edition::firstOrCreate(['name' => 'Erstpressung']));
    newRecord(['title' => 'Other', 'kind' => 'CD']);

    $response = $this->get(route('records.export', ['kind' => 'LP']))->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toStartWith("\u{FEFF}ID;Art;Künstler;Titel")
        ->toContain("'=HYPERLINK")
        ->toContain('12,5')
        ->toContain('Erstpressung')
        ->not->toContain('Other');
});

test('records can be imported from csv without changing existing records', function () {
    $existing = newRecord(['title' => 'Bestehend']);
    $csv = "\u{FEFF}ID;Art;Künstler;Titel;Label;Erscheinungsjahr;Aktueller Preis;Zusatzinfos;Zum Verkauf;Verkaufsdatum\n"
        ."{$existing->id};LP;Kraftwerk;Überschrieben;Kling Klang;;;;;\n"
        .";lp;Neu!;Neu! 75;Brain;1975;12,50;Erstpressung, Boxset;ja;\n"
        .";LP;;Ohne Künstler;Brain;;;;;\n"
        .";CD;Can;'=Formel;Spoon;abc;;;nein;\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);
    $this->post(route('records.import.store'), ['file' => $file])
        ->assertRedirect(route('records.import'))
        ->assertSessionHas('warning')
        ->assertSessionHas('import_errors', fn ($errors) => count($errors) === 2);

    expect($existing->fresh()->title)->toBe('Bestehend');

    $imported = Record::firstWhere('title', 'Neu! 75');
    expect($imported)->not->toBeNull()
        ->and($imported->kind)->toBe('LP')
        ->and($imported->release_year)->toBe(1975)
        ->and((float) $imported->current_price)->toBe(12.5)
        ->and($imported->selling)->toBeTrue()
        ->and($imported->editions->pluck('name')->sort()->values()->all())->toBe(['Boxset', 'Erstpressung'])
        ->and($imported->prices()->count())->toBe(1)
        ->and(Record::count())->toBe(2);
});

test('the dashboard shows tiles and the latest records', function () {
    newRecord(['title' => 'Ralf und Florian', 'current_price' => '10']);
    newRecord(['title' => 'Verkauft', 'sold' => true, 'current_price' => '99']);

    $this->get('/')->assertOk()
        ->assertSee('Platten im Bestand')
        ->assertSee('10,00 €')
        ->assertSee('Ralf und Florian');
});

test('the pdf exports are linked', function () {
    $record = newRecord(['selling' => true]);

    $this->get('/')->assertSee(route('records.print'), false);
    $this->get(route('records.index', ['status' => 'selling']))->assertSee(route('records.print'), false);
    $this->get(route('artists.show', $record->artist_id))->assertSee(route('artists.print', $record->artist_id), false);
    $this->get(route('labels.show', $record->label_id))->assertSee(route('labels.print', $record->label_id), false);

    $this->get(route('records.print'))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('a scanned barcode opens the record from the collection', function () {
    $record = newRecord(['title' => 'Mit Barcode', 'barcode' => '5 099996 602317']);

    // Spaces and the leading zero of the EAN form of a UPC code do not matter.
    $this->get(route('records.barcode', ['code' => '5099996602317']))->assertRedirect(route('records.show', $record));

    $upc = newRecord(['title' => 'UPC', 'barcode' => '075596061920']);
    $this->get(route('records.barcode', ['code' => '0075596061920']))->assertRedirect(route('records.show', $upc));
});

test('several records with the barcode are listed', function () {
    newRecord(['title' => 'Als LP', 'barcode' => '4006381333931']);
    newRecord(['title' => 'Als CD', 'kind' => 'CD', 'barcode' => '4006381333931']);
    newRecord(['title' => 'Andere', 'barcode' => '1234567890128']);

    $this->get(route('records.barcode', ['code' => '4006381333931']))
        ->assertRedirect(route('records.index', ['barcode' => '4006381333931']));

    $this->get(route('records.index', ['barcode' => '4006381333931']))
        ->assertSee('Als LP')->assertSee('Als CD')->assertDontSee('Andere');
});

test('an unknown barcode offers to add the record via discogs', function () {
    newRecord(['barcode' => '4006381333931']);

    $this->get(route('records.barcode', ['code' => '9999999999994']))
        ->assertRedirect(route('records.index', ['barcode' => '9999999999994']));

    $this->get(route('records.index', ['barcode' => '9999999999994']))
        ->assertSee('Keine Platte mit dem Barcode 9999999999994 in der Sammlung.')
        ->assertSee(route('records.create', ['barcode' => '9999999999994']), false);

    config(['services.discogs.token' => 'test-token']);
    app()->forgetInstance(DiscogsClient::class);
    $this->get(route('records.create', ['barcode' => '9999999999994']))
        ->assertSee('value="9999999999994"', false)
        ->assertSee('data-autosearch', false);
});

test('the navigation offers the barcode scanner', function () {
    $this->get('/')->assertSee('data-barcode-scan="collection"', false)->assertSee('id="barcode-modal"', false);
});

test('artists, labels and platforms share create, edit and delete', function (string $resource, string $model, string $field) {
    $this->get(route($resource.'.create'))->assertOk()->assertSee('name="'.$field.'"', false);

    $this->post(route($resource.'.store'), [$field => 'Sky', 'description' => 'Hamburg'])->assertSessionHas('info');
    $item = $model::firstWhere('name', 'Sky');
    expect($item->description)->toBe('Hamburg');

    // A second entry with the same name is refused.
    $this->post(route($resource.'.store'), [$field => 'Sky'])->assertSessionHas('error');
    expect($model::where('name', 'Sky')->count())->toBe(1);

    $this->get(route($resource.'.edit', $item))->assertOk()->assertSee('value="Sky"', false);
    $this->put(route($resource.'.update', $item), [$field => 'Sky Records', 'description' => ''])->assertRedirect(route($resource.'.index'));
    expect($item->fresh()->name)->toBe('Sky Records')->and($item->fresh()->description)->toBeNull();

    // Renaming to the name of another entry is a validation error.
    $model::create(['name' => 'Ohr']);
    $this->put(route($resource.'.update', $item), [$field => 'Ohr'])->assertSessionHasErrors($field);

    $this->get(route($resource.'.show', $item))->assertOk()->assertSee('Sky Records');
    $this->delete(route($resource.'.destroy', $item))->assertSessionHas('info');
    expect($model::find($item->id))->toBeNull();
    $this->get(route($resource.'.show', $item))->assertNotFound();
})->with([
    'artists' => ['artists', Artist::class, 'artist_name'],
    'labels' => ['labels', Label::class, 'label_name'],
    'platforms' => ['platforms', Platform::class, 'platform_name'],
]);

test('the pdf of a label lists the artists, the pdf of an artist the labels', function () {
    $record = newRecord(['artist' => 'Harmonia', 'label' => 'Brain']);

    expect($this->get(route('labels.print', $record->label_id))->assertOk()->headers->get('content-type'))->toContain('pdf');
    $this->get(route('artists.print', $record->artist_id))->assertOk();

    // The PDF itself is compressed, so the columns are checked in the rendered view.
    $html = fn (string $relation, $item) => view('groups.print', [
        'item' => $item, 'records' => $item->records()->with($relation)->get(), 'totalValue' => 0, 'relation' => $relation,
    ])->render();
    expect($html('artist', $record->label))->toContain('Harmonia')
        ->and($html('label', $record->artist))->toContain('Brain');

    $this->get('/platforms/1/print')->assertNotFound();
});

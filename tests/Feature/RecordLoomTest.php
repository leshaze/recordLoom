<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\Platform;
use App\Models\PriceHistory;
use App\Models\Record;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

function makeRecord(array $attributes = []): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = 'Autobahn';
    $record->current_price = '20';
    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }
    $record->save();

    return $record;
}

test('all list and detail pages render', function () {
    $record = makeRecord(['selling' => true]);
    $platform = Platform::create(['name' => 'Discogs']);

    foreach ([
        '/',
        route('records.index'),
        route('records.selling'),
        route('records.create'),
        route('records.show', $record),
        route('records.edit', $record),
        route('artists.index'),
        route('artists.show', $record->artist_id),
        route('artists.edit', $record->artist_id),
        route('labels.index'),
        route('labels.show', $record->label_id),
        route('labels.edit', $record->label_id),
        route('platforms.index'),
        route('platforms.show', $platform),
        route('platforms.edit', $platform),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('pdf exports render', function () {
    $record = makeRecord(['selling' => true]);

    $this->get(route('records.print'))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('artists.print', $record->artist_id))->assertOk();
    $this->get(route('labels.print', $record->label_id))->assertOk();
});

test('a record can be created with new artist, label and a comma price', function () {
    $this->post(route('records.store'), [
        'kind' => 'CD',
        'artist_name' => 'Neu!',
        'title' => 'Neu! 75',
        'label_name' => 'Brain',
        'country_name' => 'Germany',
        'platform' => 'Discogs',
        'current_price' => '12,50',
    ])->assertRedirect(route('records.index'));

    $record = Record::firstWhere('title', 'Neu! 75');
    expect($record->artist->name)->toBe('Neu!')
        ->and($record->label->name)->toBe('Brain')
        ->and($record->country->name)->toBe('Germany')
        ->and($record->platform->name)->toBe('Discogs')
        ->and((float) $record->current_price)->toBe(12.5)
        ->and($record->prices()->count())->toBe(1);
});

test('updating the price adds a price history entry only when it changes', function () {
    $record = makeRecord();
    $payload = [
        'kind' => 'LP',
        'artist_name' => 'Kraftwerk',
        'title' => 'Autobahn',
        'label_name' => 'Kling Klang',
        'current_price' => '20,00',
        'sold' => 'on',
        'sold_price' => '25,5',
    ];

    $this->put(route('records.update', $record), $payload)->assertRedirect(route('records.index'));
    expect(PriceHistory::count())->toBe(0);

    $this->put(route('records.update', $record), [...$payload, 'current_price' => '30'])->assertRedirect();
    $record->refresh();
    expect(PriceHistory::count())->toBe(1)
        ->and((bool) $record->sold)->toBeTrue()
        ->and((float) $record->sold_price)->toBe(25.5);
});

test('deleting a record also deletes its price history', function () {
    $record = makeRecord();
    PriceHistory::forceCreate(['price' => '10', 'record_id' => $record->id]);

    $this->delete(route('records.destroy', $record))->assertRedirect(route('records.index'));

    expect(Record::count())->toBe(0)->and(PriceHistory::count())->toBe(0);
});

test('artists with records can not be deleted', function () {
    $record = makeRecord();

    $this->delete(route('artists.destroy', $record->artist_id))->assertSessionHas('error');
    expect(Artist::count())->toBe(1);
});

test('the primary key can not be changed through the update form', function () {
    $artist = Artist::create(['name' => 'Can']);

    $this->put(route('artists.update', $artist), ['artist_name' => 'CAN', 'id' => 999])->assertRedirect();

    expect($artist->fresh()->name)->toBe('CAN')->and(Artist::find(999))->toBeNull();
});

test('flash messages are escaped', function () {
    $this->post(route('artists.store'), ['artist_name' => '<script>alert(1)</script>'])->assertRedirect();

    $this->followingRedirects()
        ->get(route('artists.create'))
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('platform urls must be http links', function () {
    $this->post(route('platforms.store'), ['platform_name' => 'Evil', 'url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('url');

    $this->post(route('platforms.store'), ['platform_name' => 'Discogs', 'url' => 'https://www.discogs.com'])
        ->assertSessionHasNoErrors();

    $platform = Platform::create(['name' => 'Legacy']);
    $platform->url = 'javascript:alert(1)';

    expect($platform->safe_url)->toBeNull()
        ->and(Platform::firstWhere('name', 'Discogs')->safe_url)->toBe('https://www.discogs.com');
});

test('autocomplete returns matching entries', function () {
    makeRecord();

    $this->getJson(route('autocomplete', ['search' => 'artist', 'term' => 'kraft']))
        ->assertOk()
        ->assertJsonPath('0.name', 'Kraftwerk');

    $this->getJson(route('autocomplete', ['search' => 'all', 'term' => 'auto']))
        ->assertOk()
        ->assertJsonPath('0.type', 'record');

    $this->getJson(route('autocomplete', ['search' => 'title', 'term' => 'auto', 'artist_id' => Artist::first()->id]))
        ->assertOk()
        ->assertJsonCount(1);

    $this->getJson(route('autocomplete', ['search' => 'unknown', 'term' => 'x']))
        ->assertOk()
        ->assertExactJson([]);
});

test('removed insecure routes are gone', function () {
    $record = makeRecord();

    $this->get('/record/'.$record->id.'/delete')->assertNotFound();
    $this->get('/login/1/edit')->assertNotFound();
    expect(Record::count())->toBe(1);
});

test('guests are redirected to the login page', function () {
    auth()->logout();
    $record = makeRecord();

    foreach (['/', route('records.index'), route('records.show', $record), route('artists.index'), route('autocomplete'), route('records.print'), route('profile.edit')] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->delete(route('records.destroy', $record))->assertRedirect(route('login'));
    $this->post(route('artists.store'), ['artist_name' => 'Guest'])->assertRedirect(route('login'));
    expect(Record::count())->toBe(1)->and(Artist::where('name', 'Guest')->exists())->toBeFalse();
});

test('public registration is disabled', function () {
    auth()->logout();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertNotFound();

    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});

test('users can be created with the artisan command', function () {
    $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com'])
        ->expectsQuestion('Password', 'a-long-password')
        ->expectsQuestion('Confirm password', 'a-long-password')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->exists())->toBeTrue();

    $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com'])
        ->expectsQuestion('Password', 'short')
        ->expectsQuestion('Confirm password', 'short')
        ->assertFailed();
});

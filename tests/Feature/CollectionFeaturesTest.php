<?php

use App\Models\Artist;
use App\Models\Edition;
use App\Models\Label;
use App\Models\Record;
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

test('the old selling page redirects to the filtered list', function () {
    $this->get(route('records.selling'))->assertRedirect(route('records.index', ['status' => 'selling']));
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

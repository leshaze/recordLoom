<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use App\Support\CollectionStats;
use App\Support\DuplicateRecords;

function dupRecord(array $attributes = []): Record
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

function newRecordForm(array $data = []): array
{
    return ['kind' => 'LP', 'artist_name' => 'Kraftwerk', 'title' => 'Autobahn', 'label_name' => 'Kling Klang', ...$data];
}

test('possible duplicates are found by barcode, Discogs release and catalog number on the same label', function () {
    $existing = dupRecord(['barcode' => '0 77779 64582 6', 'catalog_number' => '1C 062-29 524', 'discogs_release_id' => 123]);

    $reasons = fn (array $data) => DuplicateRecords::find($data)->first()['reasons'] ?? null;

    // EAN with leading zero, spaces and dashes still match.
    expect($reasons(['barcode' => '77779-64582-6']))->toBe(['gleicher Barcode'])
        ->and($reasons(['discogs_release_id' => '123']))->toBe(['gleiches Discogs-Release'])
        ->and($reasons(['catalog_number' => '1c06229524', 'label_name' => 'kling klang']))->toBe(['gleiche Katalog-Nr.'])
        ->and($reasons(['catalog_number' => '1C 062-29 524']))->toBe(['gleiche Katalog-Nr.']);

    // Same catalog number on another label, short barcodes and empty input are no duplicates.
    expect(DuplicateRecords::find(['catalog_number' => '1C 062-29 524', 'label_name' => 'EMI']))->toBeEmpty()
        ->and(DuplicateRecords::find(['barcode' => '123']))->toBeEmpty()
        ->and(DuplicateRecords::find([]))->toBeEmpty()
        ->and(DuplicateRecords::find(['discogs_release_id' => 123], $existing->id))->toBeEmpty();
});

test('a possible duplicate is only saved after confirming', function () {
    dupRecord(['barcode' => '4006381333931', 'title' => 'Radio-Aktivität']);

    $this->post(route('records.store'), newRecordForm(['barcode' => '4006381333931']))
        ->assertRedirect(route('records.create'))
        ->assertSessionHas('error');
    expect(Record::count())->toBe(1);

    // The form shows the warning again with the entered data.
    $this->get(route('records.create'))
        ->assertSee('Diese Platte ist vielleicht schon in deiner Sammlung:')
        ->assertSee('Radio-Aktivität')
        ->assertSee('gleicher Barcode')
        ->assertSee('name="confirm_duplicate"', false)
        ->assertSee('value="4006381333931"', false);

    $this->post(route('records.store'), newRecordForm(['barcode' => '4006381333931', 'confirm_duplicate' => '1']))
        ->assertSessionHas('info');
    expect(Record::count())->toBe(2);

    // Without anything to compare, a record is saved right away.
    $this->post(route('records.store'), newRecordForm(['title' => 'Ralf und Florian']))->assertSessionHas('info');
    expect(Record::count())->toBe(3);
});

test('the form checks for duplicates while typing', function () {
    dupRecord(['catalog_number' => 'KS 1234', 'title' => 'Trans Europa Express']);

    $this->getJson(route('records.duplicates', ['catalog_number' => 'ks-1234', 'label_name' => 'Kling Klang']))
        ->assertOk()->assertJsonPath('html', fn (string $html) => str_contains($html, 'Trans Europa Express'));

    $this->getJson(route('records.duplicates', ['catalog_number' => 'KS 9999']))
        ->assertOk()->assertJsonPath('html', fn (string $html) => trim($html) === '');

    $this->get(route('records.create'))->assertSee('id="duplicate-warning"', false);
});

test('the record page shows the gain or loss since buying', function () {
    $gain = dupRecord(['buy_price' => '20', 'current_price' => '35']);
    $loss = dupRecord(['title' => 'Computerwelt', 'buy_price' => '40', 'current_price' => '30']);
    $sold = dupRecord(['title' => 'Tour de France', 'buy_price' => '10', 'sold' => true, 'sold_price' => '25', 'current_price' => '30']);
    $unknown = dupRecord(['title' => 'Ralf und Florian', 'current_price' => '30']);

    expect($gain->valueChange())->toBe(['amount' => 15.0, 'percent' => 75.0])
        ->and($loss->valueChange())->toBe(['amount' => -10.0, 'percent' => -25.0])
        ->and($sold->valueChange())->toBe(['amount' => 15.0, 'percent' => 150.0])
        ->and($unknown->valueChange())->toBeNull();

    $this->get(route('records.show', $gain))->assertSee('Wertentwicklung')->assertSee('+15,00 €')->assertSee('+75,0 %');
    $this->get(route('records.show', $loss))->assertSee('−10,00 €')->assertSee('−25,0 %');
    $this->get(route('records.show', $sold))->assertSee('Gewinn / Verlust')->assertSee('+15,00 €')->assertDontSee('Wertentwicklung');
    $this->get(route('records.show', $unknown))->assertDontSee('Wertentwicklung');
});

test('the dashboard balance compares buy prices with current and sale prices', function () {
    dupRecord(['buy_price' => '20', 'current_price' => '35']);
    dupRecord(['buy_price' => '40', 'current_price' => '30']);
    dupRecord(['current_price' => '100']); // no buy price: not part of the balance
    dupRecord(['buy_price' => '50', 'current_price' => '60', 'lost' => true]);
    dupRecord(['buy_price' => '10', 'sold' => true, 'sold_price' => '25']);

    expect(CollectionStats::balance())->toBe([
        'stock' => ['count' => 2, 'cost' => 60.0, 'value' => 65.0, 'change' => ['amount' => 5.0, 'percent' => 8.3]],
        'sold' => ['count' => 1, 'cost' => 10.0, 'value' => 25.0, 'change' => ['amount' => 15.0, 'percent' => 150.0]],
        'without_buy_price' => 1,
    ]);

    $this->get('/')->assertOk()->assertSee('Bilanz')->assertSee('2 Platten mit Kaufpreis')->assertSee('+5,00 €')
        ->assertSee('1 Platte im Bestand ohne Kaufpreis ist nicht berücksichtigt.');
});

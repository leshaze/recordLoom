<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\PriceHistory;
use App\Models\Record;
use App\Support\CollectionStats;

function statsRecord(array $attributes = []): Record
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

function priceEntry(Record $record, string $price, string $date): void
{
    $entry = new PriceHistory;
    $entry->record_id = $record->id;
    $entry->price = $price;
    $entry->created_at = $date;
    $entry->save();
}

test('the value per month follows the price history, additions and sales', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $a = statsRecord(['current_price' => '30', 'created_at' => '2026-01-10']);
    priceEntry($a, '10', '2026-01-10');
    priceEntry($a, '30', '2026-03-01');

    statsRecord(['current_price' => '5', 'created_at' => '2026-02-05']); // no history: current price
    statsRecord(['current_price' => '100', 'created_at' => '2026-01-20', 'sold' => true, 'sold_on' => '2026-02-10', 'sold_price' => '120']);
    statsRecord(['current_price' => '999', 'created_at' => '2026-01-01', 'lost' => true]);

    expect(CollectionStats::valueByMonth())->toBe([
        ['month' => '2026-01', 'value' => 110.0],
        ['month' => '2026-02', 'value' => 15.0],
        ['month' => '2026-03', 'value' => 35.0],
    ]);
});

test('the value chart covers at most 36 months', function () {
    $this->travelTo('2026-09-15');
    statsRecord(['current_price' => '10', 'created_at' => '2015-01-01']);

    $months = CollectionStats::valueByMonth();
    expect($months)->toHaveCount(36)
        ->and($months[0]['month'])->toBe('2023-10')
        ->and(end($months))->toBe(['month' => '2026-09', 'value' => 10.0]);
});

test('top lists, gradings and years only count what they should', function () {
    $this->travelTo('2027-06-01');
    statsRecord(['artist' => 'Can', 'current_price' => '50', 'grading_media' => 100, 'created_at' => '2025-05-01']);
    statsRecord(['artist' => 'Can', 'current_price' => '20', 'grading_media' => 70, 'created_at' => '2026-01-01']);
    statsRecord(['artist' => 'Neu!', 'current_price' => '60', 'created_at' => '2026-01-01']);
    statsRecord(['artist' => 'Faust', 'current_price' => '500', 'sold' => true, 'sold_on' => '2026-02-01', 'sold_price' => '450.5', 'created_at' => '2025-01-01']);
    statsRecord(['artist' => 'Cluster']);

    expect(CollectionStats::top(Artist::class)->map(fn ($artist) => [$artist->name, (float) $artist->value, $artist->count])->all())
        ->toBe([['Can', 70.0, 2], ['Neu!', 60.0, 1]]);

    expect(collect(CollectionStats::gradings())->map(fn ($row) => [$row['grading']['german'] ?? null, $row['count']])->all())
        ->toBe([['M-', 1], ['VG++', 1], [null, 2]]);

    expect(CollectionStats::years())->toBe([
        ['year' => 2027, 'added' => 1, 'sold' => 0, 'revenue' => 0.0],
        ['year' => 2026, 'added' => 2, 'sold' => 1, 'revenue' => 450.5],
        ['year' => 2025, 'added' => 2, 'sold' => 0, 'revenue' => 0.0],
    ]);
});

test('the dashboard shows the chart and the new cards', function () {
    $this->travelTo('2026-03-15');
    statsRecord(['artist' => 'Can', 'current_price' => '20', 'created_at' => '2026-01-01']);

    $this->get('/')->assertOk()
        ->assertSee('valueChart')
        ->assertSee('Top 5 Künstler nach Wert')
        ->assertSeeInOrder(['Can', '20,00 €'])
        ->assertSee('Grading Media im Bestand')
        ->assertSee('Pro Jahr');
});

test('the dashboard works without records', function () {
    $this->get('/')->assertOk()->assertDontSee('valueChart')->assertSee('Noch keine Preise erfasst.');
});

<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\PriceHistory;
use App\Models\Record;
use Illuminate\Support\Facades\DB;

function priceRecord(array $attributes = []): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = 'Autobahn';
    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }
    $record->save();

    return $record;
}

test('old text prices become numbers and nothing is lost', function () {
    $record = priceRecord(['note' => 'Erstpressung']);

    // Text as the old app stored it (SQLite keeps text that is not a number in a decimal column).
    DB::table('records')->where('id', $record->id)->update([
        'current_price' => '12,5', 'buy_price' => '1.234,50 €', 'sold_price' => 'ca. 20',
    ]);
    DB::table('price_history')->insert([
        ['record_id' => $record->id, 'price' => '9,99', 'created_at' => '2024-05-01 10:00:00', 'updated_at' => '2024-05-01 10:00:00'],
        ['record_id' => $record->id, 'price' => 'viel', 'created_at' => '2024-06-01 10:00:00', 'updated_at' => '2024-06-01 10:00:00'],
    ]);

    $migration = require database_path('migrations/2026_09_29_000000_store_prices_as_decimals.php');
    $migration->up();
    $migration->up(); // Running it again changes nothing.

    $record->refresh();
    expect($record->current_price)->toBe('12.50')
        ->and($record->buy_price)->toBe('1234.50')
        ->and($record->sold_price)->toBeNull()
        ->and($record->note)->toBe("Erstpressung\nVerkaufspreis (alt): ca. 20\nPreisentwicklung (alt, 2024-06-01): viel")
        ->and(PriceHistory::pluck('price')->all())->toBe(['9.99'])
        ->and((float) Record::sum('current_price'))->toBe(12.5);
});

test('prices are sorted as numbers', function () {
    priceRecord(['title' => 'Neun', 'current_price' => '9']);
    priceRecord(['title' => 'Hundert', 'current_price' => '100']);
    priceRecord(['title' => 'Zwanzig', 'current_price' => '20.5']);

    $this->get(route('records.index', ['sort' => 'price', 'dir' => 'desc']))->assertSeeInOrder(['Hundert', 'Zwanzig', 'Neun']);
});

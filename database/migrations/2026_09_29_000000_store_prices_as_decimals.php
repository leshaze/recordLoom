<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prices were stored as text ("12,50", "1.234,50 €" in old data). They become decimal numbers, so the
 * database can sort and sum them. Values that are not a price are appended to the note of the record,
 * nothing is lost. The migration can safely run again.
 */
return new class extends Migration
{
    private const RECORD_PRICES = [
        'current_price' => 'Aktueller Preis (alt)',
        'buy_price' => 'Kaufpreis (alt)',
        'sold_price' => 'Verkaufspreis (alt)',
    ];

    public function up(): void
    {
        $this->convertRecordPrices();
        $this->convertPriceHistory();

        if (! $this->isDecimal('records', 'current_price')) {
            Schema::table('records', function (Blueprint $table) {
                foreach (array_keys(self::RECORD_PRICES) as $column) {
                    $table->decimal($column, 10, 2)->nullable()->change();
                }
            });
        }

        if (! $this->isDecimal('price_history', 'price')) {
            Schema::table('price_history', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            foreach (array_keys(self::RECORD_PRICES) as $column) {
                $table->string($column)->nullable()->change();
            }
        });
        Schema::table('price_history', function (Blueprint $table) {
            $table->string('price')->change();
        });
    }

    private function convertRecordPrices(): void
    {
        DB::table('records')->orderBy('id')->each(function ($record) {
            $changes = [];
            $notes = [];

            foreach (self::RECORD_PRICES as $column => $label) {
                $old = $record->{$column};
                [$price, $keep] = $this->toPrice($old);
                if ($keep) {
                    $notes[] = $label.': '.trim($old);
                }
                if ((string) $price !== (string) $old) {
                    $changes[$column] = $price;
                }
            }

            if ($notes) {
                $changes['note'] = trim(implode("\n", array_filter([$record->note, ...$notes])));
            }
            if ($changes) {
                DB::table('records')->where('id', $record->id)->update($changes);
            }
        });
    }

    /**
     * Entries of the price history that are not a price are moved to the note of their record.
     */
    private function convertPriceHistory(): void
    {
        DB::table('price_history')->orderBy('id')->each(function ($entry) {
            [$price, $keep] = $this->toPrice($entry->price);

            if ($keep || $price === null) {
                if ($keep) {
                    $record = DB::table('records')->where('id', $entry->record_id)->first();
                    if ($record) {
                        $line = 'Preisentwicklung (alt'.($entry->created_at ? ', '.substr($entry->created_at, 0, 10) : '').'): '.trim($entry->price);
                        DB::table('records')->where('id', $record->id)
                            ->update(['note' => trim(implode("\n", array_filter([$record->note, $line])))]);
                    }
                }
                DB::table('price_history')->where('id', $entry->id)->delete();
            } elseif ((string) $price !== (string) $entry->price) {
                DB::table('price_history')->where('id', $entry->id)->update(['price' => $price]);
            }
        });
    }

    /**
     * "12,5" → "12.50", "1.234,50 €" → "1234.50". Returns [price, keep]: keep is true when the text is not a price
     * and has to be kept elsewhere.
     *
     * @return array{0: ?string, 1: bool}
     */
    private function toPrice(mixed $value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [null, false];
        }

        $text = str_replace(['€', 'EUR', ' ', "\u{00A0}"], '', trim((string) $value));
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $text)) {
            $text = str_replace(['.', ','], ['', '.'], $text);
        } elseif (substr_count($text, ',') === 1 && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        }

        if (! is_numeric($text) || (float) $text < 0) {
            return [null, true];
        }

        return [number_format(round((float) $text, 2), 2, '.', ''), false];
    }

    private function isDecimal(string $table, string $column): bool
    {
        return in_array(Schema::getColumnType($table, $column), ['decimal', 'numeric'], true);
    }
};

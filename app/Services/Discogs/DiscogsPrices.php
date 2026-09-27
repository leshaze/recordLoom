<?php

namespace App\Services\Discogs;

use App\Models\Platform;
use App\Models\PriceHistory;
use App\Models\Record;

/**
 * Market data of linked records: price suggestions per condition and the lowest offer.
 */
class DiscogsPrices
{
    public function __construct(private readonly DiscogsClient $client) {}

    /**
     * Loads the current market data. Returns a warning when the price suggestions are not available
     * (they need filled in seller settings), the marketplace statistics are stored anyway.
     */
    public function update(Record $record): ?string
    {
        if (! $record->discogs_release_id) {
            throw new DiscogsException(__('Die Platte ist nicht mit Discogs verknüpft.'));
        }

        $warning = null;
        try {
            $suggestions = $this->client->priceSuggestions($record->discogs_release_id);
            $record->discogs_price_suggestions = $suggestions ?: null;
            $currency = collect($suggestions)->pluck('currency')->filter()->first();
        } catch (DiscogsException $exception) {
            $warning = $exception->getMessage();
            $currency = null;
        }

        $stats = $this->client->marketplaceStats($record->discogs_release_id);
        $record->discogs_lowest_price = $stats['lowest_price']['value'] ?? null;
        $record->discogs_num_for_sale = $stats['num_for_sale'] ?? null;
        $record->discogs_currency = $currency ?? ($stats['lowest_price']['currency'] ?? null);
        $record->discogs_prices_updated_at = now();
        $record->save();

        return $warning;
    }

    /**
     * Uses the suggested price for the condition of the record as current price (with price history).
     */
    public function applySuggestedPrice(Record $record): void
    {
        $suggestion = $record->discogsSuggestedPrice();
        if (! $suggestion) {
            throw new DiscogsException(__('Für den Zustand dieser Platte gibt es keinen Preisvorschlag. Bitte zuerst Grading Media setzen und die Marktdaten aktualisieren.'));
        }

        $platform = Platform::firstOrCreate(['name' => 'Discogs']);
        if (! $platform->url) {
            $platform->url = 'https://www.discogs.com';
            $platform->save();
        }

        $record->current_price = number_format((float) $suggestion['value'], 2, '.', '');
        $record->save();

        $history = new PriceHistory;
        $history->price = $record->current_price;
        $history->record_id = $record->id;
        $history->platform_id = $platform->id;
        $history->save();
    }
}

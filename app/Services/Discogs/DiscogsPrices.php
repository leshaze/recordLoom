<?php

namespace App\Services\Discogs;

use App\Models\Platform;
use App\Models\PriceHistory;
use App\Models\Record;

/**
 * Market data of linked records: lowest offer and price suggestions per condition.
 */
class DiscogsPrices
{
    public const SOURCE_LOWEST = 'lowest';

    public const SOURCE_SUGGESTION = 'suggestion';

    public function __construct(private readonly DiscogsClient $client) {}

    /**
     * Loads the current market data. Missing price suggestions are no error: Discogs only provides them
     * when the seller settings of the account are filled in, the reason is stored and shown on the record page.
     */
    public function update(Record $record): void
    {
        if (! $record->discogs_release_id) {
            throw new DiscogsException(__('Die Platte ist nicht mit Discogs verknüpft.'));
        }

        try {
            $stats = $this->client->marketplaceStats($record->discogs_release_id);
        } catch (DiscogsException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
            $stats = [];
        }

        $record->discogs_lowest_price = $stats['lowest_price']['value'] ?? null;
        $record->discogs_num_for_sale = $stats['num_for_sale'] ?? 0;
        $currency = $stats['lowest_price']['currency'] ?? null;

        try {
            $suggestions = array_filter($this->client->priceSuggestions($record->discogs_release_id), fn ($price) => isset($price['value']));
            $record->discogs_price_suggestions = $suggestions ?: null;
            $record->discogs_suggestions_note = $suggestions ? null : __('Discogs hat für diese Pressung keine Preisvorschläge.');
            $currency ??= collect($suggestions)->pluck('currency')->filter()->first();
        } catch (DiscogsException $exception) {
            $record->discogs_price_suggestions = null;
            $record->discogs_suggestions_note = $exception->getCode() === 404
                ? __('Discogs liefert keine Preisvorschläge. Dafür müssen im Discogs-Konto die Verkäufer-Einstellungen ausgefüllt sein.')
                : $exception->getMessage();
        }

        $record->discogs_currency = $currency;
        $record->discogs_prices_updated_at = now();
        $record->save();
    }

    /**
     * Uses the lowest offer or the suggestion for the condition of the record as current price
     * and adds it to the price history with the vendor "Discogs".
     */
    public function apply(Record $record, string $source): void
    {
        $value = match ($source) {
            self::SOURCE_LOWEST => $record->discogs_lowest_price,
            self::SOURCE_SUGGESTION => $record->discogsSuggestedPrice()['value'] ?? null,
            default => null,
        };

        if ($value === null) {
            throw new DiscogsException($source === self::SOURCE_SUGGESTION
                ? __('Für den Zustand dieser Platte gibt es keinen Preisvorschlag. Bitte zuerst Grading Media setzen und die Marktdaten aktualisieren.')
                : __('Bei Discogs wird die Platte gerade nicht angeboten.'));
        }

        $platform = Platform::firstOrCreate(['name' => 'Discogs']);
        if (! $platform->url) {
            $platform->url = 'https://www.discogs.com';
            $platform->save();
        }

        $record->current_price = number_format((float) $value, 2, '.', '');
        $record->save();

        $history = new PriceHistory;
        $history->price = $record->current_price;
        $history->record_id = $record->id;
        $history->platform_id = $platform->id;
        $history->save();
    }
}

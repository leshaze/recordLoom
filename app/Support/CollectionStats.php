<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\Label;
use App\Models\PriceHistory;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for the dashboard. "In stock" means neither sold nor lost, like the value tile.
 */
class CollectionStats
{
    /**
     * Months shown in the value chart at most.
     */
    public const MONTHS = 36;

    public static function inStock(): Builder
    {
        return Record::where('sold', false)->where('lost', false);
    }

    /**
     * Value of the collection at the end of each month: every record counts from the day it was added
     * until it was sold (sold_on), with its price that was valid then (price history, else the current price).
     * Lost records and sold records without a sale date are left out, their date is not known.
     *
     * @return list<array{month: string, value: float}>
     */
    public static function valueByMonth(): array
    {
        // Plain rows instead of models: casting dates and decimals for every record and month is far too slow
        // on a Raspberry Pi. Dates are compared as text ("2026-03-15 10:00:00"), months as "2026-03".
        $records = Record::where('lost', false)
            ->where(fn (Builder $query) => $query->where('sold', false)->orWhereNotNull('sold_on'))
            ->toBase()
            ->get(['id', 'created_at', 'sold', 'sold_on', 'current_price']);

        if ($records->isEmpty()) {
            return [];
        }

        $history = [];
        foreach (PriceHistory::orderBy('created_at')->orderBy('id')->toBase()->cursor(['record_id', 'price', 'created_at']) as $entry) {
            $history[$entry->record_id][] = [substr((string) $entry->created_at, 0, 7), (float) $entry->price];
        }

        $end = now()->startOfMonth();
        $first = $records->pluck('created_at')->filter()->min();
        $start = $first ? Carbon::parse($first)->startOfMonth() : $end->copy();
        if ($start->diffInMonths($end) >= self::MONTHS) {
            $start = $end->copy()->subMonths(self::MONTHS - 1);
        }

        $months = [];
        for ($month = $start->copy(); $month <= $end; $month->addMonth()) {
            $months[] = $month->format('Y-m');
        }
        $values = array_fill(0, count($months), 0.0);

        foreach ($records as $record) {
            $added = $record->created_at ? substr((string) $record->created_at, 0, 7) : '';
            $sold = $record->sold && $record->sold_on ? substr((string) $record->sold_on, 0, 7) : null;
            $entries = $history[$record->id] ?? [];
            // Before the first entry of the price history the first known price counts.
            $price = $entries ? $entries[0][1] : (float) $record->current_price;
            $next = 0;

            foreach ($months as $index => $month) {
                while (isset($entries[$next]) && $entries[$next][0] <= $month) {
                    $price = $entries[$next++][1];
                }
                if ($added <= $month && ($sold === null || $sold > $month)) {
                    $values[$index] += $price;
                }
            }
        }

        return array_map(fn (string $month, float $value) => ['month' => $month, 'value' => round($value, 2)], $months, $values);
    }

    /**
     * The five artists or labels with the highest value in stock.
     *
     * @param  class-string<Artist|Label>  $model
     * @return Collection<int, Artist|Label>
     */
    public static function top(string $model, int $limit = 5): Collection
    {
        $inStock = fn (Builder $query) => $query->where('sold', false)->where('lost', false);

        return $model::query()
            ->withSum(['records as value' => $inStock], 'current_price')
            ->withCount(['records as count' => $inStock])
            ->orderByDesc('value')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->filter(fn ($item) => $item->value > 0)
            ->values();
    }

    /**
     * Records in stock per media grading, best grade first; "not graded" last.
     *
     * @return list<array{grading: ?array, count: int}>
     */
    public static function gradings(): array
    {
        $counts = self::inStock()->selectRaw('grading_media, COUNT(*) as count')->groupBy('grading_media')
            ->pluck('count', 'grading_media');

        $rows = [];
        foreach (array_keys(Grading::SCALE) as $value) {
            if ($counts->has($value)) {
                $rows[] = ['grading' => Grading::for($value), 'count' => (int) $counts[$value]];
            }
        }

        $notGraded = $counts->filter(fn ($count, $value) => ! isset(Grading::SCALE[(int) $value]))->sum();
        if ($notGraded > 0) {
            $rows[] = ['grading' => null, 'count' => (int) $notGraded];
        }

        return $rows;
    }

    /**
     * Records added and sold per year, with the sum of the sale prices; newest year first.
     *
     * @return list<array{year: int, added: int, sold: int, revenue: float}>
     */
    public static function years(): array
    {
        $years = [];

        foreach (Record::toBase()->cursor(['created_at', 'sold', 'sold_on', 'sold_price']) as $record) {
            if ($record->created_at) {
                $year = (int) substr((string) $record->created_at, 0, 4);
                $years[$year]['added'] = ($years[$year]['added'] ?? 0) + 1;
            }
            if ($record->sold && $record->sold_on) {
                $year = (int) substr((string) $record->sold_on, 0, 4);
                $years[$year]['sold'] = ($years[$year]['sold'] ?? 0) + 1;
                $years[$year]['revenue'] = ($years[$year]['revenue'] ?? 0) + (float) $record->sold_price;
            }
        }

        krsort($years);

        return collect($years)->map(fn (array $year, int $number) => [
            'year' => $number,
            'added' => $year['added'] ?? 0,
            'sold' => $year['sold'] ?? 0,
            'revenue' => round($year['revenue'] ?? 0, 2),
        ])->values()->all();
    }
}

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
        $records = Record::where('lost', false)
            ->where(fn (Builder $query) => $query->where('sold', false)->orWhereNotNull('sold_on'))
            ->get(['id', 'created_at', 'sold', 'sold_on', 'current_price']);

        if ($records->isEmpty()) {
            return [];
        }

        $history = PriceHistory::orderBy('created_at')->orderBy('id')
            ->get(['record_id', 'price', 'created_at'])
            ->groupBy('record_id');

        $end = now()->endOfMonth();
        $start = Carbon::parse($records->min('created_at'))->startOfMonth();
        if ($start->diffInMonths($end) >= self::MONTHS) {
            $start = $end->copy()->subMonths(self::MONTHS - 1)->startOfMonth();
        }

        $months = [];
        for ($month = $start->copy(); $month <= $end; $month->addMonth()) {
            $monthEnd = $month->copy()->endOfMonth();
            $value = 0.0;

            foreach ($records as $record) {
                if ($record->created_at > $monthEnd || ($record->sold && $record->sold_on <= $monthEnd)) {
                    continue;
                }
                $value += self::priceAt($record, $history->get($record->id), $monthEnd);
            }

            $months[] = ['month' => $month->format('Y-m'), 'value' => round($value, 2)];
        }

        return $months;
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

        foreach (Record::get(['created_at', 'sold', 'sold_on', 'sold_price']) as $record) {
            if ($record->created_at) {
                $years[$record->created_at->year]['added'] = ($years[$record->created_at->year]['added'] ?? 0) + 1;
            }
            if ($record->sold && $record->sold_on) {
                $year = $record->sold_on->year;
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

    /**
     * @param  Collection<int, PriceHistory>|null  $history  sorted by date
     */
    private static function priceAt(Record $record, ?Collection $history, Carbon $date): float
    {
        if (! $history || $history->isEmpty()) {
            return (float) $record->current_price;
        }

        $price = $history->first()->price; // Before the first entry the first known price counts.
        foreach ($history as $entry) {
            if ($entry->created_at > $date) {
                break;
            }
            $price = $entry->price;
        }

        return (float) $price;
    }
}

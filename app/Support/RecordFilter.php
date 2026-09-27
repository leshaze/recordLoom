<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Filters and sorting of the record list, taken from the query string.
 */
class RecordFilter
{
    public const PER_PAGE = [15, 30, 60, 100];

    public const STATUS = [
        'available' => 'Im Bestand',
        'selling' => 'Zum Verkauf vorgemerkt',
        'sold' => 'Verkauft',
        'lost' => 'Verloren',
    ];

    public const SORTS = [
        'artist' => 'Künstler',
        'title' => 'Titel',
        'label' => 'Label',
        'year' => 'Jahr',
        'price' => 'Preis',
        'grading' => 'Grading',
        'created' => 'Hinzugefügt',
    ];

    /**
     * @var array<string, mixed>
     */
    public array $values;

    public function __construct(Request $request)
    {
        $sort = $request->query('sort');
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page');

        $this->values = [
            'q' => trim((string) $request->query('q', '')),
            'kind' => in_array($request->query('kind'), ['LP', 'CD'], true) ? $request->query('kind') : null,
            'label' => $request->integer('label') ?: null,
            'country' => $request->integer('country') ?: null,
            'edition' => $request->integer('edition') ?: null,
            'status' => is_string($status) && array_key_exists($status, self::STATUS) ? $status : null,
            'sort' => is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'artist',
            'dir' => $request->query('dir') === 'desc' ? 'desc' : 'asc',
            'per_page' => in_array($perPage, self::PER_PAGE, true) ? $perPage : self::PER_PAGE[0],
        ];
    }

    public function query(): Builder
    {
        $query = Record::query();
        $v = $this->values;

        if ($v['q'] !== '') {
            $like = '%'.$v['q'].'%';
            $query->where(function (Builder $query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('catalog_number', 'like', $like)
                    ->orWhere('matrix_number', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhere('archive_number', 'like', $like)
                    ->orWhereHas('artist', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('label', fn (Builder $query) => $query->where('name', 'like', $like));
            });
        }

        $query->when($v['kind'], fn (Builder $query, $kind) => $query->where('kind', $kind))
            ->when($v['label'], fn (Builder $query, $label) => $query->where('label_id', $label))
            ->when($v['country'], fn (Builder $query, $country) => $query->where('country_id', $country))
            ->when($v['edition'], fn (Builder $query, $edition) => $query->whereHas('editions', fn (Builder $query) => $query->whereKey($edition)));

        match ($v['status']) {
            'available' => $query->where('sold', false)->where('lost', false),
            'selling' => $query->where('selling', true)->where('sold', false),
            'sold' => $query->where('sold', true),
            'lost' => $query->where('lost', true),
            default => null,
        };

        $dir = $v['dir'];
        match ($v['sort']) {
            'artist' => $query->orderBy(Artist::select('name')->whereColumn('artists.id', 'records.artist_id'), $dir)->orderBy('title'),
            'title' => $query->orderBy('title', $dir),
            'label' => $query->orderBy(Label::select('name')->whereColumn('labels.id', 'records.label_id'), $dir)->orderBy('title'),
            'year' => $query->orderBy('release_year', $dir)->orderBy('title'),
            'price' => $query->orderBy(DB::raw('current_price + 0'), $dir),
            'grading' => $query->orderBy('grading_media', $dir)->orderBy('grading_cover', $dir),
            'created' => $query->orderBy('created_at', $dir)->orderBy('id', $dir),
        };

        return $query;
    }

    /**
     * Query string for a column header link: toggles the direction when the column is already sorted.
     *
     * @return array<string, mixed>
     */
    public function sortLink(string $sort): array
    {
        $dir = $this->values['sort'] === $sort && $this->values['dir'] === 'asc' ? 'desc' : 'asc';

        return array_filter([...$this->values, 'sort' => $sort, 'dir' => $dir], fn ($value) => $value !== null && $value !== '');
    }

    public function isActive(): bool
    {
        $v = $this->values;

        return $v['q'] !== '' || $v['kind'] || $v['label'] || $v['country'] || $v['edition'] || $v['status'];
    }
}

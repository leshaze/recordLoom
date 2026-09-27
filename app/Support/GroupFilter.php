<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Search, sorting and paging of the artist and label lists, taken from the query string.
 * Counts and values of the records are calculated by the database, so they can be sorted.
 */
class GroupFilter
{
    public const SORTS = [
        'name' => 'Name',
        'lps' => 'LPs',
        'cds' => 'CDs',
        'lp_value' => 'Wert LPs',
        'cd_value' => 'Wert CDs',
    ];

    public const RECORDS = [
        'with' => 'Mit Platten',
        'without' => 'Ohne Platten',
    ];

    /**
     * @var array<string, mixed>
     */
    public array $values;

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(Request $request, private string $model, public string $route)
    {
        $sort = $request->query('sort');
        $records = $request->query('records');
        $perPage = (int) $request->query('per_page');

        $this->values = [
            'q' => trim((string) $request->query('q', '')),
            'records' => is_string($records) && array_key_exists($records, self::RECORDS) ? $records : null,
            'sort' => is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'name',
            'dir' => $request->query('dir') === 'desc' ? 'desc' : 'asc',
            'per_page' => in_array($perPage, RecordFilter::PER_PAGE, true) ? $perPage : RecordFilter::PER_PAGE[0],
        ];
    }

    public function query(): Builder
    {
        $v = $this->values;
        $kind = fn (string $kind) => fn (Builder $query) => $query->where('kind', $kind);
        // Sold records are counted but no longer part of the value.
        $value = fn (string $kind) => fn (Builder $query) => $query->where('kind', $kind)->where('sold', false);

        $query = $this->model::query()
            ->withCount(['records as lps' => $kind('LP'), 'records as cds' => $kind('CD')])
            ->withSum(['records as lp_value' => $value('LP')], 'current_price')
            ->withSum(['records as cd_value' => $value('CD')], 'current_price');

        if ($v['q'] !== '') {
            $like = '%'.$v['q'].'%';
            $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('description', 'like', $like));
        }

        match ($v['records']) {
            'with' => $query->has('records'),
            'without' => $query->doesntHave('records'),
            default => null,
        };

        if ($v['sort'] !== 'name') {
            // Entries without records or prices come last in both directions.
            $query->orderByRaw("COALESCE({$v['sort']}, 0) = 0")->orderBy($v['sort'], $v['dir']);
        }

        return $query->orderBy('name', $v['sort'] === 'name' ? $v['dir'] : 'asc');
    }

    /**
     * Query string for a column header link: toggles the direction when the column is already sorted.
     * Numbers start with the highest value.
     *
     * @return array<string, mixed>
     */
    public function sortLink(string $sort): array
    {
        $first = $sort === 'name' ? 'asc' : 'desc';
        $dir = $this->values['sort'] === $sort && $this->values['dir'] === $first ? ($first === 'asc' ? 'desc' : 'asc') : $first;

        return array_filter([...$this->values, 'sort' => $sort, 'dir' => $dir], fn ($value) => $value !== null && $value !== '');
    }

    public function isActive(): bool
    {
        return $this->values['q'] !== '' || $this->values['records'] !== null;
    }
}

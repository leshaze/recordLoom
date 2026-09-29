<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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

    public string $route = 'records.index';

    /**
     * Route parameters of the list, e.g. ['artist' => 5] for the records on the page of an artist.
     *
     * @var array<string, int>
     */
    public array $routeParameters = [];

    /**
     * Fixed restriction of the list: [column, id], e.g. ['artist_id', 5].
     *
     * @var array{0: string, 1: int}|null
     */
    public ?array $scope = null;

    private const SESSION = 'records.list';

    /**
     * Columns a list can be restricted to.
     */
    private const SCOPES = ['artist_id', 'label_id', 'platform_id'];

    public function __construct(Request $request)
    {
        $sort = $request->query('sort');
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page');

        $this->values = [
            'q' => trim((string) $request->query('q', '')),
            'barcode' => self::normalizeBarcode((string) $request->query('barcode', '')),
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

    /**
     * Only the records of one artist, label or platform, shown on its page.
     *
     * @param  array<string, int>  $routeParameters
     */
    public function within(string $column, int $id, string $route, array $routeParameters): static
    {
        $this->scope = [$column, $id];
        $this->route = $route;
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * URL of the list with the given query string.
     *
     * @param  array<string, mixed>  $query
     */
    public function url(array $query = []): string
    {
        return route($this->route, [...$this->routeParameters, ...$query]);
    }

    /**
     * Remembers the list, so the record page can page through it and link back to it.
     */
    public function remember(Request $request): void
    {
        $request->session()->put(self::SESSION, [
            'route' => $this->route,
            'parameters' => $this->routeParameters,
            'scope' => $this->scope,
            'query' => $request->query(),
        ]);
    }

    /**
     * The list last shown (all records if there is none).
     *
     * @return array{0: self, 1: array<string, mixed>} the filter and its query string
     */
    public static function remembered(Request $request): array
    {
        $list = $request->session()->get(self::SESSION, []);
        $query = is_array($list['query'] ?? null) ? $list['query'] : [];
        $filter = new self(Request::create('/', 'GET', $query));

        if (in_array($list['scope'][0] ?? null, self::SCOPES, true) && isset($list['scope'][1], $list['route'])) {
            $filter->within($list['scope'][0], (int) $list['scope'][1], $list['route'], $list['parameters'] ?? []);
        }

        return [$filter, $query];
    }

    /**
     * Only the digits of a barcode ("5 099996 602317" → "5099996602317").
     */
    public static function normalizeBarcode(string $barcode): string
    {
        return substr(preg_replace('/\D/', '', $barcode), 0, 20);
    }

    /**
     * Records with the barcode, independent of spaces or dashes and of the leading zero
     * that turns a 12 digit UPC into a 13 digit EAN.
     */
    public static function whereBarcode(Builder $query, string $barcode): Builder
    {
        $digits = ltrim(self::normalizeBarcode($barcode), '0');
        $cleaned = "REPLACE(REPLACE(REPLACE(COALESCE(barcode, ''), ' ', ''), '-', ''), '.', '')";
        $stored = in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb', 'sqlsrv'], true)
            ? "TRIM(LEADING '0' FROM {$cleaned})"
            : "LTRIM({$cleaned}, '0')";

        return $query->whereRaw("{$stored} = ?", [$digits === '' ? '-' : $digits]);
    }

    public function query(): Builder
    {
        $query = Record::query();
        $v = $this->values;

        if ($this->scope) {
            $query->where($this->scope[0], $this->scope[1]);
        }

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

        if ($v['barcode'] !== '') {
            self::whereBarcode($query, $v['barcode']);
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
            'price' => $query->orderBy('current_price', $dir),
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

        return $v['q'] !== '' || $v['barcode'] !== '' || $v['kind'] || $v['label'] || $v['country'] || $v['edition'] || $v['status'];
    }
}

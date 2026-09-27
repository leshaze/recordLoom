<?php

namespace App\Services\Discogs;

use App\Models\Edition;
use App\Models\Record;
use Illuminate\Support\Str;

/**
 * Translates Discogs API data into RecordLoom record fields.
 */
class ReleaseMapper
{
    /**
     * Discogs format descriptions => German name of the Zusatzinfo.
     */
    public const EDITION_DESCRIPTIONS = [
        'limited edition' => 'Limitierte Auflage',
        'box set' => 'Boxset',
        'picture disc' => 'Picture Disc',
    ];

    /**
     * Words in the free format text that mean coloured vinyl.
     */
    private const COLOURS = [
        'red', 'blue', 'green', 'yellow', 'white', 'clear', 'transparent', 'translucent', 'orange', 'purple',
        'pink', 'gold', 'silver', 'splatter', 'marbled', 'coloured', 'colored', 'grey', 'gray', 'violet', 'magenta',
    ];

    /**
     * Grading of RecordLoom (percent) => Discogs condition used for the price suggestion.
     */
    public const CONDITIONS = [
        100 => 'Near Mint (NM or M-)',
        85 => 'Near Mint (NM or M-)',
        70 => 'Very Good Plus (VG+)',
        50 => 'Very Good Plus (VG+)',
        35 => 'Very Good (VG)',
        25 => 'Very Good (VG)',
        15 => 'Good Plus (G+)',
        10 => 'Good Plus (G+)',
        5 => 'Good (G)',
    ];

    /**
     * Form data of a Discogs release (only fields Discogs knows, all others stay untouched).
     *
     * @param  array<string, mixed>  $release  response of GET /releases/{id}
     * @param  int|null  $originalYear  year of the master release (first release), if known
     * @return array<string, mixed>
     */
    public function toRecordData(array $release, ?int $originalYear = null): array
    {
        $formats = $release['formats'] ?? [];
        $descriptions = collect($formats)->flatMap(fn ($format) => $format['descriptions'] ?? [])
            ->map(fn ($description) => Str::lower($description));
        $year = (int) ($release['year'] ?? 0) ?: null;
        $isReissue = $descriptions->contains(fn ($d) => in_array($d, ['reissue', 'repress'], true));

        return array_filter([
            'discogs_release_id' => $release['id'] ?? null,
            'kind' => $this->kind($formats),
            'artist_name' => $this->artistName($release['artists'] ?? []),
            'title' => $release['title'] ?? null,
            'label_name' => isset($release['labels'][0]['name']) ? $this->cleanName($release['labels'][0]['name']) : null,
            'catalog_number' => $this->catalogNumber($release['labels'][0]['catno'] ?? null),
            'barcode' => $this->identifier($release['identifiers'] ?? [], 'Barcode'),
            'matrix_number' => $this->identifier($release['identifiers'] ?? [], 'Matrix / Runout', ' / '),
            'country_name' => $release['country'] ?? null,
            'release_year' => $isReissue ? ($originalYear ?: null) : $year,
            'reissue_year' => $isReissue ? $year : null,
            'editions' => $this->editionIds($formats, $descriptions->all()),
            'cover_url' => $this->coverUrl($release['images'] ?? []),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * Compact search result for the selection list.
     *
     * @param  array<string, mixed>  $result  item of GET /database/search
     * @return array<string, mixed>
     */
    public function toSearchResult(array $result): array
    {
        return [
            'id' => $result['id'] ?? null,
            'title' => $result['title'] ?? '',
            'year' => $result['year'] ?? null,
            'country' => $result['country'] ?? null,
            'format' => implode(', ', array_unique($result['format'] ?? [])),
            'label' => $result['label'][0] ?? null,
            'catno' => $result['catno'] ?? null,
            'thumb' => $this->isImageUrl($result['thumb'] ?? null) ? $result['thumb'] : null,
            'url' => isset($result['id']) ? self::releaseUrl((int) $result['id']) : null,
        ];
    }

    public static function releaseUrl(int $id): string
    {
        return 'https://www.discogs.com/release/'.$id;
    }

    /**
     * Search criteria for an existing record: barcode first, then catalog number, then artist and title.
     *
     * @return array<int, array<string, string>>
     */
    public function searchCriteriaFor(Record $record): array
    {
        return array_values(array_filter([
            filled($record->barcode) ? ['barcode' => $record->barcode] : null,
            filled($record->catalog_number) ? ['catno' => $record->catalog_number, 'artist' => $record->artist?->name] : null,
            ['artist' => (string) $record->artist?->name, 'release_title' => $record->title],
        ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $formats
     */
    private function kind(array $formats): ?string
    {
        foreach ($formats as $format) {
            $name = Str::lower($format['name'] ?? '');
            if ($name === 'vinyl') {
                return 'LP';
            }
            if (in_array($name, ['cd', 'cdr', 'sacd'], true)) {
                return 'CD';
            }
        }

        return null;
    }

    /**
     * "Nirvana (2)" → "Nirvana", several artists joined like Discogs does ("A & B", "A Feat. B").
     *
     * @param  array<int, array<string, mixed>>  $artists
     */
    private function artistName(array $artists): ?string
    {
        $name = '';
        foreach ($artists as $index => $artist) {
            $name .= $this->cleanName(($artist['anv'] ?? '') ?: ($artist['name'] ?? ''));
            if ($index < count($artists) - 1) {
                $join = trim($artist['join'] ?? '') ?: ',';
                $name .= $join === ',' ? ', ' : ' '.$join.' ';
            }
        }

        return trim($name) ?: null;
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s\(\d+\)$/', '', $name));
    }

    private function catalogNumber(?string $catno): ?string
    {
        return $catno === null || Str::lower($catno) === 'none' ? null : $catno;
    }

    /**
     * @param  array<int, array<string, mixed>>  $identifiers
     */
    private function identifier(array $identifiers, string $type, ?string $join = null): ?string
    {
        $values = collect($identifiers)
            ->filter(fn ($identifier) => ($identifier['type'] ?? null) === $type)
            ->pluck('value')
            ->filter()
            ->unique();

        if ($values->isEmpty()) {
            return null;
        }

        return Str::limit($join ? $values->implode($join) : $values->first(), 250, '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $formats
     * @param  array<int, string>  $descriptions
     * @return array<int, int>
     */
    private function editionIds(array $formats, array $descriptions): array
    {
        $names = [];
        foreach (self::EDITION_DESCRIPTIONS as $description => $edition) {
            if (in_array($description, $descriptions, true)) {
                $names[] = $edition;
            }
        }

        $text = Str::lower(collect($formats)->pluck('text')->filter()->implode(' '));
        foreach (self::COLOURS as $colour) {
            if (preg_match('/\b'.$colour.'\b/', $text)) {
                $names[] = 'Farbiges Vinyl';
                break;
            }
        }

        return Edition::whereIn('name', $names)->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     */
    private function coverUrl(array $images): ?string
    {
        $image = collect($images)->firstWhere('type', 'primary') ?? ($images[0] ?? null);
        $url = $image['uri'] ?? null;

        return $this->isImageUrl($url) ? $url : null;
    }

    private function isImageUrl(?string $url): bool
    {
        $parts = $url ? parse_url($url) : null;

        return ($parts['scheme'] ?? null) === 'https' && in_array($parts['host'] ?? '', DiscogsClient::IMAGE_HOSTS, true);
    }
}

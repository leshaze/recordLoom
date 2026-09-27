<?php

namespace App\Services\Discogs;

use App\Models\Artist;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Label;
use App\Models\Record;

/**
 * Compares a record with a Discogs release field by field and applies the chosen values.
 * Nothing is taken over without a choice: by default the existing value is kept, empty fields get the Discogs value.
 */
class DiscogsComparison
{
    public const KEEP = 'keep';

    public const DISCOGS = 'discogs';

    /**
     * Field => German label (translated in the view).
     */
    public const FIELDS = [
        'kind' => 'Art',
        'artist_name' => 'Künstler',
        'title' => 'Titel',
        'label_name' => 'Label',
        'catalog_number' => 'Katalog-Nr.',
        'barcode' => 'Barcode',
        'matrix_number' => 'Matrix-Nr.',
        'country_name' => 'Herkunftsland',
        'release_year' => 'Erscheinungsjahr',
        'reissue_year' => 'Jahr der Neuauflage',
    ];

    /**
     * @param  array<string, mixed>  $release  data of ReleaseMapper::toRecordData()
     * @return array<int, array{field: string, label: string, current: ?string, discogs: ?string, same: bool, default: string}>
     */
    public function rows(Record $record, array $release): array
    {
        $rows = [];
        foreach (self::FIELDS as $field => $label) {
            $discogs = $this->text($release[$field] ?? null);
            if ($discogs === null) {
                continue;
            }

            $current = $this->text($this->current($record, $field));
            $rows[] = [
                'field' => $field,
                'label' => $label,
                'current' => $current,
                'discogs' => $discogs,
                'same' => $current !== null && mb_strtolower($current) === mb_strtolower($discogs),
                'default' => $current === null ? self::DISCOGS : self::KEEP,
            ];
        }

        return $rows;
    }

    /**
     * Editions suggested by Discogs that the record does not have yet.
     *
     * @param  array<string, mixed>  $release
     */
    public function newEditions(Record $record, array $release)
    {
        return Edition::whereIn('id', $release['editions'] ?? [])
            ->whereNotIn('id', $record->editions()->pluck('editions.id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $release
     * @param  array<string, string>  $choices  field => keep|discogs
     */
    public function apply(Record $record, array $release, array $choices): void
    {
        foreach (array_keys(self::FIELDS) as $field) {
            if (($choices[$field] ?? self::KEEP) !== self::DISCOGS || ! isset($release[$field])) {
                continue;
            }

            $value = $release[$field];
            match ($field) {
                'artist_name' => $record->artist_id = Artist::firstOrCreate(['name' => $value])->id,
                'label_name' => $record->label_id = Label::firstOrCreate(['name' => $value])->id,
                'country_name' => $record->country_id = Country::firstOrCreate(['name' => $value])->id,
                default => $record->{$field} = $value,
            };
        }
    }

    private function current(Record $record, string $field): mixed
    {
        return match ($field) {
            'artist_name' => $record->artist?->name,
            'label_name' => $record->label?->name,
            'country_name' => $record->country?->name,
            default => $record->{$field},
        };
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

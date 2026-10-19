<?php

namespace App\Support;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds records that are probably the same as a new one: same barcode, same Discogs release,
 * or same catalog number (on the same label, if a label is given).
 */
class DuplicateRecords
{
    private const LIMIT = 5;

    /**
     * SQL of the catalog number without case, spaces, dashes and dots ("CBS 32 112" = "cbs-32112").
     * Like SQLite's LOWER(), PHP's strtolower() only changes ASCII letters, so both sides match.
     */
    private const CATALOG_SQL = "LOWER(REPLACE(REPLACE(REPLACE(COALESCE(catalog_number, ''), ' ', ''), '-', ''), '.', ''))";

    /**
     * @param  array<string, mixed>  $data  barcode, catalog_number, label_name, discogs_release_id
     * @return Collection<int, array{record: Record, reasons: list<string>}>
     */
    public static function find(array $data, ?int $exceptId = null): Collection
    {
        $barcode = ltrim(RecordFilter::normalizeBarcode((string) ($data['barcode'] ?? '')), '0');
        $catalog = self::catalog($data['catalog_number'] ?? null);
        $release = (int) ($data['discogs_release_id'] ?? 0);
        $label = strtolower(trim((string) ($data['label_name'] ?? '')));

        // Short barcodes are typing errors or partial input rather than a product code.
        $barcode = strlen($barcode) >= 7 ? $barcode : '';
        if ($barcode === '' && $catalog === '' && $release === 0) {
            return collect();
        }

        $records = Record::with(['artist', 'label'])
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->where(function (Builder $query) use ($barcode, $catalog, $release, $label) {
                if ($barcode !== '') {
                    $query->orWhere(fn (Builder $query) => RecordFilter::whereBarcode($query, $barcode));
                }
                if ($release > 0) {
                    $query->orWhere('discogs_release_id', $release);
                }
                if ($catalog !== '') {
                    $query->orWhere(fn (Builder $query) => $query->whereRaw(self::CATALOG_SQL.' = ?', [$catalog])
                        ->when($label !== '', fn (Builder $query) => $query->whereHas('label', fn (Builder $query) => $query->whereRaw('LOWER(name) = ?', [$label]))));
                }
            })
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();

        return $records->map(fn (Record $record) => [
            'record' => $record,
            'reasons' => array_values(array_filter([
                $barcode !== '' && ltrim(RecordFilter::normalizeBarcode((string) $record->barcode), '0') === $barcode ? 'gleicher Barcode' : null,
                $release > 0 && $record->discogs_release_id === $release ? 'gleiches Discogs-Release' : null,
                $catalog !== '' && self::catalog($record->catalog_number) === $catalog ? 'gleiche Katalog-Nr.' : null,
            ])),
        ]);
    }

    private static function catalog(mixed $value): string
    {
        return strtolower(str_replace([' ', '-', '.'], '', trim((string) $value)));
    }
}

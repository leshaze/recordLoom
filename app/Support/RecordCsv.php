<?php

namespace App\Support;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV format of the record export and import (semicolon separated, UTF-8, for Excel).
 */
class RecordCsv
{
    public const DELIMITER = ';';

    /**
     * Column header => record data key (as used by SaveRecord).
     */
    public const COLUMNS = [
        'ID' => 'id',
        'Art' => 'kind',
        'Künstler' => 'artist_name',
        'Titel' => 'title',
        'Label' => 'label_name',
        'Herkunftsland' => 'country_name',
        'Anbieter' => 'platform',
        'Katalog-Nr.' => 'catalog_number',
        'Matrix-Nr.' => 'matrix_number',
        'Barcode' => 'barcode',
        'Archiv-Nr.' => 'archive_number',
        'Erscheinungsjahr' => 'release_year',
        'Neuauflage' => 'reissue_year',
        'Grading Media' => 'grading_media',
        'Grading Cover' => 'grading_cover',
        'Aktueller Preis' => 'current_price',
        'Kaufpreis' => 'buy_price',
        'Zusatzinfos' => 'editions',
        'Zum Verkauf' => 'selling',
        'Verkauft' => 'sold',
        'Verkaufsdatum' => 'sold_on',
        'Verkauft an' => 'sold_to',
        'Verkaufspreis' => 'sold_price',
        'Verloren' => 'lost',
        'Notiz' => 'note',
    ];

    public const FLAGS = ['selling', 'sold', 'lost'];

    public static function download(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, array_keys(self::COLUMNS), self::DELIMITER, escape: '');

            $query->chunk(500, function ($records) use ($out) {
                foreach ($records as $record) {
                    fputcsv($out, array_map(self::protect(...), self::row($record)), self::DELIMITER, escape: '');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<int, string|int|null>
     */
    public static function row(Record $record): array
    {
        return [
            $record->id,
            $record->kind,
            $record->artist?->name,
            $record->title,
            $record->label?->name,
            $record->country?->name,
            $record->platform?->name,
            $record->catalog_number,
            $record->matrix_number,
            $record->barcode,
            $record->archive_number,
            $record->release_year,
            $record->reissue_year,
            $record->grading_media,
            $record->grading_cover,
            self::price($record->current_price),
            self::price($record->buy_price),
            $record->editions->pluck('name')->implode(', '),
            $record->selling ? 'ja' : 'nein',
            $record->sold ? 'ja' : 'nein',
            $record->sold_on?->format('d.m.Y'),
            $record->sold_to,
            self::price($record->sold_price),
            $record->lost ? 'ja' : 'nein',
            $record->note,
        ];
    }

    /**
     * Prices with decimal comma, like Excel expects it in German.
     */
    private static function price(?string $price): ?string
    {
        return $price === null || $price === '' ? null : str_replace('.', ',', $price);
    }

    /**
     * Prevent spreadsheet formulas (CSV injection) by prefixing dangerous values with an apostrophe.
     */
    private static function protect(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Reads the CSV file and returns the rows as data arrays keyed like COLUMNS values.
     *
     * @return array<int, array<string, string>> line number => data
     */
    public static function read(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\x{FEFF}/u', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\n");
        $delimiter = substr_count((string) $firstLine, ';') >= substr_count((string) $firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, null, $delimiter, escape: '');
        $keys = array_map(fn ($name) => self::COLUMNS[trim((string) $name)] ?? null, $header ?: []);

        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, $delimiter, escape: '')) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($keys as $index => $key) {
                if ($key !== null) {
                    $value = trim((string) ($values[$index] ?? ''));
                    // Remove the apostrophe added by protect().
                    if (str_starts_with($value, "'") && in_array(substr($value, 1, 1), ['=', '+', '-', '@'], true)) {
                        $value = substr($value, 1);
                    }
                    $row[$key] = $value;
                }
            }
            $rows[$line] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, string> header names that are known
     */
    public static function knownHeaders(): array
    {
        return array_keys(self::COLUMNS);
    }
}

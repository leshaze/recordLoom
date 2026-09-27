<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Backup of the SQLite database: a fingerprint of the content to detect changes and a compressed copy.
 */
class DatabaseBackup
{
    private const STATE_FILE = 'backup/state.json';

    /**
     * Hash over the content of all tables (except technical ones). Changes only when data changes,
     * including deletions, and not when pages are just viewed.
     */
    public function fingerprint(): string
    {
        $hash = hash_init('sha256');
        $ignored = config('backup.ignored_tables');

        $tables = collect(Schema::getTableListing(schemaQualified: false))
            ->reject(fn (string $table) => in_array($table, $ignored, true) || str_starts_with($table, 'sqlite_'))
            ->sort()
            ->values();

        foreach ($tables as $table) {
            hash_update($hash, "\n#{$table}\n");
            $columns = Schema::getColumnListing($table);
            sort($columns);
            $query = DB::table($table)->select($columns)->orderBy($columns[0]);
            foreach (array_slice($columns, 1) as $column) {
                $query->orderBy($column);
            }
            foreach ($query->cursor() as $row) {
                hash_update($hash, json_encode($row)."\n");
            }
        }

        return hash_final($hash);
    }

    /**
     * Consistent copy of the database (also while it is in use), gzip compressed. Returns the path.
     */
    public function createCompressedCopy(): string
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('The backup by mail only supports SQLite databases.');
        }

        $directory = storage_path('app/private/backup');
        if (! is_dir($directory)) {
            mkdir($directory, 0770, true);
        }

        $copy = $directory.'/recordloom-'.now()->format('Y-m-d-His').'.sqlite';
        DB::statement('VACUUM INTO ?', [$copy]);

        $compressed = $copy.'.gz';
        $in = fopen($copy, 'rb');
        $out = gzopen($compressed, 'wb9');
        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 1024));
        }
        fclose($in);
        gzclose($out);
        unlink($copy);

        return $compressed;
    }

    /**
     * @return array{fingerprint?: string, sent_at?: string}
     */
    public function state(): array
    {
        return json_decode(Storage::disk('local')->get(self::STATE_FILE) ?? '{}', true) ?: [];
    }

    public function rememberSent(string $fingerprint): void
    {
        Storage::disk('local')->put(self::STATE_FILE, json_encode([
            'fingerprint' => $fingerprint,
            'sent_at' => now()->toIso8601String(),
        ]));
    }
}

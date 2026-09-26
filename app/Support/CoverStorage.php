<?php

namespace App\Support;

use App\Models\Record;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores cover images outside of the public folder. They are delivered by RecordController::cover().
 */
class CoverStorage
{
    private const DISK = 'local';

    private const THUMBNAIL_WIDTH = 300;

    public static function store(Record $record, UploadedFile $file): void
    {
        self::delete($record);

        $name = 'covers/'.$record->id.'-'.Str::random(12);
        $record->cover_path = $file->storeAs($name.'.'.$file->extension(), options: self::DISK) ?: null;
        $record->cover_thumbnail_path = self::thumbnail($file, $name.'-thumb.jpg');
    }

    public static function delete(Record $record): void
    {
        $paths = array_filter([$record->cover_path, $record->cover_thumbnail_path]);
        if ($paths) {
            Storage::disk(self::DISK)->delete($paths);
        }

        $record->cover_path = null;
        $record->cover_thumbnail_path = null;
    }

    public static function path(Record $record, bool $thumbnail = false): ?string
    {
        $path = $thumbnail ? ($record->cover_thumbnail_path ?? $record->cover_path) : $record->cover_path;

        return $path && Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->path($path) : null;
    }

    /**
     * Small JPEG version for the lists. Only created when the GD extension is available.
     */
    private static function thumbnail(UploadedFile $file, string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $image) {
            return null;
        }

        $thumbnail = imagescale($image, min(self::THUMBNAIL_WIDTH, imagesx($image)));
        ob_start();
        imagejpeg($thumbnail, null, 80);
        $data = ob_get_clean();

        return Storage::disk(self::DISK)->put($path, $data) ? $path : null;
    }
}

<?php

namespace App\Actions;

use App\Models\Artist;
use App\Models\Country;
use App\Models\Label;
use App\Models\Platform;
use App\Models\PriceHistory;
use App\Models\Record;
use App\Support\CoverStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a record from validated form (or import) data.
 */
class SaveRecord
{
    /**
     * Plain columns that are copied from the data when present.
     */
    private const COLUMNS = [
        'kind', 'title', 'catalog_number', 'matrix_number', 'barcode', 'archive_number',
        'release_year', 'reissue_year', 'grading_media', 'grading_cover',
        'current_price', 'buy_price', 'note', 'sold_on', 'sold_to', 'sold_price',
    ];

    private const FLAGS = ['selling', 'sold', 'lost'];

    /**
     * @param  array<string, mixed>  $data  validated data
     */
    public function __invoke(Record $record, array $data, ?UploadedFile $cover = null, bool $removeCover = false): Record
    {
        return DB::transaction(function () use ($record, $data, $cover, $removeCover) {
            $oldPrice = $record->exists ? $record->getOriginal('current_price') : null;

            foreach (self::COLUMNS as $column) {
                if (array_key_exists($column, $data)) {
                    $record->{$column} = $data[$column] === '' ? null : $data[$column];
                }
            }
            foreach (self::FLAGS as $flag) {
                if (array_key_exists($flag, $data)) {
                    $record->{$flag} = (bool) $data[$flag];
                }
            }
            if (! $record->grading_media) {
                $record->grading_media = null;
            }
            if (! $record->grading_cover) {
                $record->grading_cover = null;
            }

            $record->artist_id = $this->resolve(Artist::class, $data['artist_name'], $data['artist_id'] ?? null);
            $record->label_id = $this->resolve(Label::class, $data['label_name'], $data['label_id'] ?? null);

            if (array_key_exists('country_name', $data)) {
                $record->country_id = filled($data['country_name'])
                    ? $this->resolve(Country::class, $data['country_name'], $data['country_id'] ?? null)
                    : null;
            }
            if (array_key_exists('platform', $data)) {
                $record->platform_id = filled($data['platform'])
                    ? $this->resolve(Platform::class, $data['platform'], $data['platform_id'] ?? null)
                    : null;
            }

            $record->save();

            if (array_key_exists('editions', $data) || ! $record->wasRecentlyCreated) {
                $record->editions()->sync(Arr::wrap($data['editions'] ?? []));
            }

            if ($removeCover) {
                CoverStorage::delete($record);
                $record->save();
            }
            if ($cover) {
                CoverStorage::store($record, $cover);
                $record->save();
            }

            $priceChanged = $record->current_price !== null
                && ($oldPrice === null || (float) $oldPrice !== (float) $record->current_price);
            if ($priceChanged) {
                $history = new PriceHistory;
                $history->price = $record->current_price;
                $history->record_id = $record->id;
                $history->platform_id = $record->platform_id;
                $history->save();
            }

            return $record;
        });
    }

    /**
     * Use the given id when it belongs to the given name, otherwise find or create the entry by name.
     *
     * @param  class-string<Artist|Label|Country|Platform>  $model
     */
    private function resolve(string $model, string $name, mixed $id): int
    {
        $name = trim($name);

        if ($id) {
            $existing = $model::find($id);
            if ($existing && $existing->name === $name) {
                return $existing->id;
            }
        }

        return $model::firstOrCreate(['name' => $name])->id;
    }
}

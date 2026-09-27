<?php

namespace App\Services\Discogs;

use App\Models\Record;
use App\Support\CoverStorage;

class DiscogsCovers
{
    public function __construct(private readonly DiscogsClient $client) {}

    /**
     * Downloads the cover from Discogs and stores it for the record (without saving the record).
     * Returns a warning instead of throwing, because a missing cover should never stop saving.
     */
    public function store(Record $record, string $url): ?string
    {
        try {
            [$content, $extension] = $this->client->downloadImage($url);
            CoverStorage::storeContents($record, $content, $extension);
        } catch (DiscogsException $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}

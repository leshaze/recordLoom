<?php

use App\Models\Record;
use App\Services\Discogs\DiscogsClient;
use App\Services\Discogs\DiscogsException;
use App\Services\Discogs\DiscogsPrices;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('discogs:update-prices {--all : All linked records instead of only the ones marked for sale}', function (DiscogsClient $client, DiscogsPrices $prices) {
    if (! $client->isConfigured()) {
        $this->error('DISCOGS_TOKEN is not set.');

        return 1;
    }

    $records = Record::whereNotNull('discogs_release_id')
        ->unless($this->option('all'), fn ($query) => $query->where('selling', true)->where('sold', false))
        ->orderBy('id')
        ->get();

    $failed = 0;
    foreach ($records as $index => $record) {
        try {
            $warning = $prices->update($record);
            $this->line($record->title.($warning ? ' – '.$warning : ''));
        } catch (DiscogsException $exception) {
            $failed++;
            $this->warn($record->title.' – '.$exception->getMessage());
        }

        // Two requests per record, Discogs allows 60 per minute.
        if ($index < $records->count() - 1 && ! app()->runningUnitTests()) {
            sleep(3);
        }
    }

    $this->info(count($records).' records checked, '.$failed.' failed.');

    return 0;
})->purpose('Update the Discogs market data of linked records');

if (config('services.discogs.nightly_prices') && config('services.discogs.token')) {
    Schedule::command('discogs:update-prices')->dailyAt('03:17')->withoutOverlapping();
}

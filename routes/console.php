<?php

use App\Mail\DatabaseBackupMail;
use App\Models\Artist;
use App\Models\Label;
use App\Models\PriceHistory;
use App\Models\Record;
use App\Services\Backup\DatabaseBackup;
use App\Services\Discogs\DiscogsClient;
use App\Services\Discogs\DiscogsException;
use App\Services\Discogs\DiscogsPrices;
use App\Support\CatchUpSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('discogs:update-prices
    {--all : All linked records instead of only the ones marked for sale}
    {--if-due : Only run when the last run is at least DISCOGS_PRICE_INTERVAL_HOURS ago (for the scheduler)}', function (DiscogsClient $client, DiscogsPrices $prices, CatchUpSchedule $schedule) {
    $interval = config('services.discogs.price_interval_hours').' hours';
    if ($this->option('if-due') && ! $schedule->isDue('discogs:update-prices', $interval)) {
        return 0;
    }

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
            $prices->update($record);
            $this->line($record->title.($record->discogs_suggestions_note ? ' – '.$record->discogs_suggestions_note : ''));
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
    $schedule->markRun('discogs:update-prices');

    return 0;
})->purpose('Update the Discogs market data of linked records');

if (config('services.discogs.nightly_prices') && config('services.discogs.token')) {
    // Every 15 minutes the scheduler checks whether 24 hours have passed, so a missed run is caught up
    // as soon as the server (e.g. a Raspberry Pi that is not always on) runs again.
    Schedule::command('discogs:update-prices --if-due')->everyFifteenMinutes()->withoutOverlapping();
}

Artisan::command('backup:mail
    {--force : Send even if nothing has changed}
    {--if-due : Only check when the last check was BACKUP_INTERVAL_DAYS calendar days ago or more (for the scheduler)}', function (DatabaseBackup $backup, CatchUpSchedule $schedule) {
    if ($this->option('if-due') && ! $schedule->isDueAfterDays('backup:mail', (int) config('backup.interval_days'))) {
        return 0;
    }

    $recipient = config('backup.mail_to');
    if (! $recipient) {
        $this->error('BACKUP_MAIL_TO is not set.');

        return 1;
    }

    $fingerprint = $backup->fingerprint();
    $state = $backup->state();
    if (! $this->option('force') && ($state['fingerprint'] ?? null) === $fingerprint) {
        $schedule->markRun('backup:mail');
        $this->info('No changes since the last backup, no mail sent.');

        return 0;
    }

    $path = $backup->createCompressedCopy();
    try {
        $sizeMb = filesize($path) / 1024 / 1024;
        if ($sizeMb > config('backup.max_attachment_mb')) {
            $this->error(sprintf('The backup is %.1f MB, too large for a mail (limit BACKUP_MAIL_MAX_MB).', $sizeMb));

            return 1;
        }

        Mail::to($recipient)->send(new DatabaseBackupMail($path, [
            __('Platten') => Record::count(),
            __('Künstler (Menü)') => Artist::count(),
            __('Labels') => Label::count(),
            __('Preiseinträge') => PriceHistory::count(),
        ], isset($state['sent_at']) ? Carbon::parse($state['sent_at'])->format('d.m.Y H:i') : null));

        $backup->rememberSent($fingerprint);
        $schedule->markRun('backup:mail');
        $this->info(sprintf('Backup (%.2f MB) sent to %s.', $sizeMb, $recipient));
    } finally {
        @unlink($path);
    }

    return 0;
})->purpose('Mail a copy of the database when it has changed since the last backup');

if (config('backup.enabled') && config('backup.mail_to')) {
    // Checked every 15 minutes, runs once per day (BACKUP_INTERVAL_DAYS): a backup that was missed while
    // the server was off is sent as soon as it runs again.
    Schedule::command('backup:mail --if-due')->everyFifteenMinutes()->withoutOverlapping();
}

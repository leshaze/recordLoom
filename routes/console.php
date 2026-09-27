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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
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

    return 0;
})->purpose('Update the Discogs market data of linked records');

if (config('services.discogs.nightly_prices') && config('services.discogs.token')) {
    Schedule::command('discogs:update-prices')->dailyAt('03:17')->withoutOverlapping();
}

Artisan::command('backup:mail {--force : Send even if nothing has changed}', function (DatabaseBackup $backup) {
    $recipient = config('backup.mail_to');
    if (! $recipient) {
        $this->error('BACKUP_MAIL_TO is not set.');

        return 1;
    }

    $fingerprint = $backup->fingerprint();
    $state = $backup->state();
    if (! $this->option('force') && ($state['fingerprint'] ?? null) === $fingerprint) {
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
        $this->info(sprintf('Backup (%.2f MB) sent to %s.', $sizeMb, $recipient));
    } finally {
        @unlink($path);
    }

    return 0;
})->purpose('Mail a copy of the database when it has changed since the last backup');

if (config('backup.enabled') && config('backup.mail_to')) {
    Schedule::command('backup:mail')->weeklyOn(0, '04:23')->withoutOverlapping();
}

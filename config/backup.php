<?php

return [
    // Weekly backup of the database by mail (only when something has changed).
    'mail_to' => env('BACKUP_MAIL_TO'),
    'enabled' => env('BACKUP_MAIL_ENABLED', true),

    // Tables that change without the collection changing (sessions, cache, queue).
    'ignored_tables' => [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations',
    ],

    // Larger attachments are not sent, most mail servers refuse them.
    'max_attachment_mb' => env('BACKUP_MAIL_MAX_MB', 20),
];

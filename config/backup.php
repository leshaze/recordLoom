<?php

return [
    // Daily backup of the database by mail (only when something has changed).
    'mail_to' => env('BACKUP_MAIL_TO'),
    'enabled' => env('BACKUP_MAIL_ENABLED', true),

    // Calendar days between two checks (1 = daily). A missed check (server switched off) is caught up
    // when the server runs again.
    'interval_days' => (int) env('BACKUP_INTERVAL_DAYS', 1),

    // Tables that change without the collection changing (sessions, cache, queue).
    'ignored_tables' => [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations',
    ],

    // Larger attachments are not sent, most mail servers refuse them.
    'max_attachment_mb' => env('BACKUP_MAIL_MAX_MB', 20),
];

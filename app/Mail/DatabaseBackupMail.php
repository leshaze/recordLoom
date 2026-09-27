<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, int>  $counts  number of entries, e.g. ['Platten' => 120]
     */
    public function __construct(
        public readonly string $path,
        public readonly array $counts,
        public readonly ?string $previousBackup,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('RecordLoom: Sicherung der Datenbank vom :date', ['date' => now()->format('d.m.Y')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.database-backup');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [Attachment::fromPath($this->path)->as(basename($this->path))->withMime('application/gzip')];
    }
}

<?php

namespace App\Notifications;

use App\Models\Record;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a seller who was interested in a record that it is no longer for sale.
 */
class RecordWithdrawn extends Notification
{
    public function __construct(private Record $record) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replace = ['title' => $this->record->title, 'artist' => $this->record->artist->name];

        return (new MailMessage)
            ->subject(__('„:title“ wird nicht mehr verkauft', $replace))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line(__('die Platte „:title“ von :artist, für die du Interesse angemeldet hast, wird nicht mehr verkauft. Dein Interesse und dein Preisvorschlag wurden gelöscht.', $replace));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'withdrawn',
            'record_id' => $this->record->id,
            'title' => $this->record->title,
            'artist' => $this->record->artist->name,
        ];
    }
}

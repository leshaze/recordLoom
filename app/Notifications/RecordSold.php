<?php

namespace App\Notifications;

use App\Models\Record;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a seller who was interested in a record whether it was sold to him or to someone else.
 */
class RecordSold extends Notification
{
    public function __construct(private Record $record, private bool $won) {}

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
            ->subject($this->won ? __('„:title“ wurde an dich verkauft', $replace) : __('„:title“ wurde an jemand anderen verkauft', $replace))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line($this->won
                ? __('die Platte „:title“ von :artist, für die du Interesse angemeldet hast, wurde an dich verkauft.', $replace)
                : __('die Platte „:title“ von :artist, für die du Interesse angemeldet hast, wurde an jemand anderen verkauft.', $replace))
            ->action(__('Platte ansehen'), route('records.show', $this->record));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->won ? 'sold_to_you' : 'sold_to_other',
            'record_id' => $this->record->id,
            'title' => $this->record->title,
            'artist' => $this->record->artist->name,
        ];
    }
}

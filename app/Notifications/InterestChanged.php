<?php

namespace App\Notifications;

use App\Models\Record;
use App\Models\User;
use App\Support\Format;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the owner of a record that a seller registered or withdrew interest or changed the offer.
 * Kinds: interest, interest_withdrawn, offer.
 */
class InterestChanged extends Notification
{
    public function __construct(private Record $record, private User $seller, private string $kind, private ?string $offer = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replace = [
            'title' => $this->record->title,
            'artist' => $this->record->artist->name,
            'seller' => $this->seller->name,
            'offer' => Format::euro($this->offer),
        ];
        $line = match (true) {
            $this->kind === 'interest_withdrawn' => __(':seller hat das Interesse an „:title“ von :artist zurückgenommen.', $replace),
            $this->kind === 'offer' && $this->offer !== null => __(':seller bietet jetzt :offer für „:title“ von :artist.', $replace),
            $this->kind === 'offer' => __(':seller hat den Preisvorschlag für „:title“ von :artist zurückgenommen.', $replace),
            $this->offer !== null => __(':seller hat Interesse an „:title“ von :artist angemeldet und bietet :offer.', $replace),
            default => __(':seller hat Interesse an „:title“ von :artist angemeldet.', $replace),
        };

        return (new MailMessage)
            ->subject(__('Neues zu „:title“', $replace))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line($line)
            ->action(__('Platte ansehen'), route('records.show', $this->record));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'record_id' => $this->record->id,
            'title' => $this->record->title,
            'artist' => $this->record->artist->name,
            'seller' => $this->seller->name,
            'offer' => $this->offer,
        ];
    }
}

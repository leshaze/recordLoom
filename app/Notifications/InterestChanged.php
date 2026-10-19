<?php

namespace App\Notifications;

use App\Models\Record;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Tells the owner of a record (in the app only) that a seller registered or withdrew interest.
 */
class InterestChanged extends Notification
{
    public function __construct(private Record $record, private User $seller, private bool $interested) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->interested ? 'interest' : 'interest_withdrawn',
            'record_id' => $this->record->id,
            'title' => $this->record->title,
            'artist' => $this->record->artist->name,
            'seller' => $this->seller->name,
        ];
    }
}

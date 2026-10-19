<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation of a user created by an admin: a link to set the own password.
 */
class Invitation extends Notification
{
    public function __construct(private string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function url(object $notifiable, string $token): string
    {
        return route('password.reset', ['token' => $token, 'email' => $notifiable->email]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Einladung zu RecordLoom'))
            ->greeting(__('Hallo :name,', ['name' => $notifiable->name]))
            ->line(__('für dich wurde ein Zugang zu RecordLoom angelegt. Lege über den folgenden Link dein Passwort fest.'))
            ->action(__('Passwort festlegen'), self::url($notifiable, $this->token))
            ->line(__('Der Link ist :days Tage gültig. Danach kannst du über „Passwort vergessen?“ einen neuen anfordern.', [
                'days' => intdiv((int) config('auth.passwords.users.expire'), 1440),
            ]));
    }
}

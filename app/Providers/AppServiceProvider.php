<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Discogs\DiscogsClient;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DiscogsClient::class, fn () => DiscogsClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admins and users keep a collection (records, artists, labels, ...), sellers only see assigned records.
        Gate::define('manage-collection', fn (User $user) => $user->managesCollection());
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // "Forgot password" mail in the language of the app; the link is valid for days, not minutes.
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject(__('Neues Passwort für RecordLoom'))
            ->greeting(__('Hallo :name,', ['name' => $user->name]))
            ->line(__('über den folgenden Link kannst du ein neues Passwort für RecordLoom festlegen.'))
            ->action(__('Passwort festlegen'), route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line(__('Der Link ist :days Tage gültig. Hast du kein neues Passwort angefordert, musst du nichts tun.', [
                'days' => intdiv((int) config('auth.passwords.users.expire'), 1440),
            ])));
    }
}

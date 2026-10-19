<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Defaults of new users, like in the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'mail_notifications' => true,
        'sees_all_records' => false,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'mail_notifications' => 'boolean',
            'sees_all_records' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSeller(): bool
    {
        return $this->role === Role::Seller;
    }

    /**
     * A seller the admin allowed to see all records that are not sold.
     */
    public function seesAllRecords(): bool
    {
        return $this->isSeller() && $this->sees_all_records;
    }

    /**
     * Channels of the notifications for this user: always in the app, by mail when the user wants it.
     *
     * @return list<string>
     */
    public function notificationChannels(): array
    {
        return $this->mail_notifications ? ['database', 'mail'] : ['database'];
    }

    /**
     * Logs the user out in all browsers (except the given session), e.g. after a password change.
     * Only possible with sessions in the database; remember-me cookies end with the new remember token.
     */
    public function endSessions(?string $except = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $this->id)
            ->when($except, fn ($query) => $query->where('id', '!=', $except))
            ->delete();
    }

    /**
     * Admins and users keep a collection; sellers only see assigned records.
     */
    public function managesCollection(): bool
    {
        return ! $this->isSeller();
    }

    /**
     * The records of the user's own collection.
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Records assigned to the seller, with the time he registered interest (null = no interest).
     */
    public function assignedRecords(): BelongsToMany
    {
        return $this->belongsToMany(Record::class, 'record_seller')->using(RecordSeller::class)->withPivot('interested_at', 'assigned', 'offer_price')->withTimestamps();
    }
}

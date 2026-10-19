<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

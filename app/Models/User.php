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
        return $this->belongsToMany(Record::class, 'record_seller')->using(RecordSeller::class)->withPivot('interested_at')->withTimestamps();
    }
}

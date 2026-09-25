<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Record extends Model
{
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function label()
    {
        return $this->belongsTo(Label::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function prices()
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * Users that marked this record as interesting.
     */
    public function interestedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'record_interests')->withTimestamps();
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}

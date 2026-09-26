<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Additional information of a record like "Boxset" or "Erstpressung" (Zusatzinfo).
 */
class Edition extends Model
{
    protected $fillable = [
        'name',
    ];

    public function records(): BelongsToMany
    {
        return $this->belongsToMany(Record::class);
    }
}

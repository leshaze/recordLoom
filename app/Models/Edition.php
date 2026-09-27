<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;

/**
 * Additional information of a record like "Boxset" or "Erstpressung" (Zusatzinfo).
 * "name" is German, "name_en" the optional English name.
 */
class Edition extends Model
{
    protected $fillable = [
        'name',
        'name_en',
    ];

    public function records(): BelongsToMany
    {
        return $this->belongsToMany(Record::class);
    }

    /**
     * Name in the current language, the German name when no English one is set.
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => App::getLocale() === 'en' && filled($this->name_en) ? $this->name_en : $this->name);
    }
}

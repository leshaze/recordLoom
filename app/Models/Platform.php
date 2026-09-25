<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Platform extends Model
{
    protected $fillable = [
        'name',
    ];

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    /**
     * The platform URL, but only if it is a http(s) link and therefore safe to use in an href.
     */
    protected function safeUrl(): Attribute
    {
        return Attribute::get(
            fn () => Str::startsWith(Str::lower((string) $this->url), ['http://', 'https://']) ? $this->url : null
        );
    }
}

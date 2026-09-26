<?php

namespace App\Models;

use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Record extends Model
{
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'selling' => 'boolean',
            'sold' => 'boolean',
            'lost' => 'boolean',
            'release_year' => 'integer',
            'reissue_year' => 'integer',
            'grading_media' => 'integer',
            'grading_cover' => 'integer',
        ];
    }

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

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class)->orderBy('name');
    }

    public function hasCover(): bool
    {
        return $this->cover_path !== null;
    }

    /**
     * Grading of media and cover as readable labels, e.g. "VG+".
     */
    public function gradingMedia(): ?array
    {
        return Grading::for($this->grading_media);
    }

    public function gradingCover(): ?array
    {
        return Grading::for($this->grading_cover);
    }
}

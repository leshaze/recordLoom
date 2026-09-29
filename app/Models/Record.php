<?php

namespace App\Models;

use App\Services\Discogs\ReleaseMapper;
use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Record extends Model
{
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'current_price' => 'decimal:2',
            'buy_price' => 'decimal:2',
            'sold_price' => 'decimal:2',
            'selling' => 'boolean',
            'sold' => 'boolean',
            'lost' => 'boolean',
            'release_year' => 'integer',
            'reissue_year' => 'integer',
            'grading_media' => 'integer',
            'grading_cover' => 'integer',
            'discogs_release_id' => 'integer',
            'discogs_price_suggestions' => 'array',
            'discogs_lowest_price' => 'decimal:2',
            'discogs_num_for_sale' => 'integer',
            'discogs_prices_updated_at' => 'datetime',
            'discogs_ignored_at' => 'datetime',
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

    public function discogsUrl(): ?string
    {
        return $this->discogs_release_id ? ReleaseMapper::releaseUrl($this->discogs_release_id) : null;
    }

    /**
     * Discogs condition matching the media grading, e.g. "Very Good Plus (VG+)".
     */
    public function discogsCondition(): ?string
    {
        return ReleaseMapper::CONDITIONS[$this->grading_media] ?? null;
    }

    /**
     * Discogs price suggestion for the condition of this record.
     *
     * @return array{currency: string, value: float}|null
     */
    public function discogsSuggestedPrice(): ?array
    {
        $condition = $this->discogsCondition();

        return $condition ? ($this->discogs_price_suggestions[$condition] ?? null) : null;
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

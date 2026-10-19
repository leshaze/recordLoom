<?php

namespace App\Models;

use App\Services\Discogs\ReleaseMapper;
use App\Support\Grading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

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

    /**
     * The user whose collection the record belongs to.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sellers the record is assigned to (assigned = true) or who registered interest because it is marked
     * for selling (assigned = false), with the time of their interest (null = no interest).
     */
    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'record_seller')->using(RecordSeller::class)->withPivot('interested_at', 'assigned', 'offer_price')->withTimestamps()->orderBy('name');
    }

    /**
     * The seller the record was sold to.
     */
    public function soldToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_to_user_id');
    }

    /**
     * Records the signed in user may see.
     */
    public static function visible(): Builder
    {
        return static::query()->visibleTo(Auth::user());
    }

    /**
     * Records a user may see: admins all, users their own collection, sellers the records assigned to them,
     * all records marked for selling and the records they registered interest in.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return match (true) {
            $user === null => $query->whereRaw('1 = 0'),
            $user->isAdmin() => $query,
            $user->isSeller() => $query->where(fn (Builder $query) => $query
                ->whereHas('sellers', fn (Builder $query) => $query->whereKey($user->id))
                ->orWhere(fn (Builder $query) => $query->where('records.sold', false)
                    ->when(! $user->seesAllRecords(), fn (Builder $query) => $query->where('records.selling', true)))),
            default => $query->where('records.user_id', $user->id),
        };
    }

    /**
     * Change of the value since buying: current price minus buy price (records in stock),
     * sale price minus buy price (sold records). Null when one of the prices is missing.
     *
     * @return array{amount: float, percent: ?float}|null
     */
    public function valueChange(): ?array
    {
        $value = $this->sold ? $this->sold_price : $this->current_price;
        if ($value === null || $this->buy_price === null) {
            return null;
        }

        return self::change((float) $this->buy_price, (float) $value);
    }

    /**
     * @return array{amount: float, percent: ?float}
     */
    public static function change(float $from, float $to): array
    {
        return [
            'amount' => round($to - $from, 2),
            'percent' => $from > 0 ? round(($to - $from) / $from * 100, 1) : null,
        ];
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

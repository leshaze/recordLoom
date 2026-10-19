{{-- Sellers: own interest and whether the record was sold to them. Owners: number of interested sellers. --}}
@if (auth()->user()->isSeller())
    @if ($record->sold_to_user_id === auth()->id())
        <span class="badge text-bg-success fw-normal"><i class="bi bi-bag-check"></i> {{ __('An dich verkauft') }}</span>
    @elseif ($record->relationLoaded('sellers') && $record->sellers->first()?->pivot->interested_at && ! $record->sold)
        <span class="badge text-bg-warning fw-normal"><i class="bi bi-star-fill"></i> {{ __('Interesse angemeldet') }}
            @if ($record->sellers->first()->pivot->offer_price !== null)
                · {{ \App\Support\Format::euro($record->sellers->first()->pivot->offer_price) }}
            @endif
        </span>
    @endif
@elseif (($record->interested_count ?? 0) > 0 && ! $record->sold)
    <span class="badge text-bg-warning fw-normal"><i class="bi bi-star-fill"></i> {{ trans_choice(':count Interessent|:count Interessenten', $record->interested_count) }}</span>
@endif

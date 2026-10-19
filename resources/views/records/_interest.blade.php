{{-- Seller: register or withdraw interest in an assigned record. --}}
<div class="card mb-3 border-warning-subtle">
    <div class="card-header"><i class="bi bi-star"></i> {{ __('Dein Interesse') }}</div>
    <div class="card-body">
        @if ($record->sold)
            @if ($record->sold_to_user_id === auth()->id())
                <p class="mb-0 text-success"><i class="bi bi-bag-check"></i> {{ __('Diese Platte wurde am :date an dich verkauft.', ['date' => \App\Support\Format::date($record->sold_on)]) }}</p>
            @else
                <p class="mb-0 text-body-secondary">{{ __('Diese Platte ist bereits verkauft.') }}</p>
            @endif
        @else
            <form method="POST" action="{{ route('records.interest', $record) }}" class="d-flex flex-wrap align-items-center gap-2">
                @csrf
                @if ($assignment->interested_at)
                    <input type="hidden" name="interested" value="0">
                    <span><i class="bi bi-star-fill text-warning"></i> {{ __('Du hast am :date Interesse angemeldet.', ['date' => \App\Support\Format::date($assignment->interested_at)]) }}</span>
                    <button type="submit" class="btn btn-sm btn-outline-secondary ms-auto">{{ __('Interesse zurücknehmen') }}</button>
                @else
                    <input type="hidden" name="interested" value="1">
                    <span class="text-body-secondary">{{ __('Möchtest du diese Platte haben?') }}</span>
                    <button type="submit" class="btn btn-sm btn-warning ms-auto"><i class="bi bi-star-fill"></i> {{ __('Interesse anmelden') }}</button>
                @endif
            </form>
        @endif
    </div>
</div>

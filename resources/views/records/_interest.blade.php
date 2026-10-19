{{-- Seller: register interest with an optional offer, change the offer, or withdraw the interest. --}}
<div class="card mb-3 border-warning-subtle">
    <div class="card-header"><i class="bi bi-star"></i> {{ __('Dein Interesse') }}</div>
    <div class="card-body">
        @if ($record->sold)
            @if ($record->sold_to_user_id === auth()->id())
                <p class="mb-0 text-success"><i class="bi bi-bag-check"></i> {{ __('Diese Platte wurde am :date an dich verkauft.', ['date' => \App\Support\Format::date($record->sold_on)]) }}</p>
            @else
                <p class="mb-0 text-body-secondary">{{ __('Diese Platte ist bereits verkauft.') }}</p>
            @endif
        @elseif ($assignment?->interested_at)
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span><i class="bi bi-star-fill text-warning"></i> {{ __('Du hast am :date Interesse angemeldet.', ['date' => \App\Support\Format::date($assignment->interested_at)]) }}</span>
                <form method="POST" action="{{ route('records.interest', $record) }}" class="ms-auto">
                    @csrf
                    <input type="hidden" name="interested" value="0">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Interesse zurücknehmen') }}</button>
                </form>
            </div>
            <form method="POST" action="{{ route('records.interest', $record) }}">
                @csrf
                <input type="hidden" name="interested" value="1">
                <label for="offer_price" class="form-label">{{ __('Dein Preisvorschlag') }}</label>
                <div class="d-flex flex-wrap gap-2">
                    <div class="input-group" style="max-width: 12rem">
                        <input type="text" inputmode="decimal" name="offer_price" id="offer_price" class="form-control @error('offer_price') is-invalid @enderror"
                            value="{{ old('offer_price', \App\Support\Format::input($assignment->offer_price)) }}">
                        <span class="input-group-text">€</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Preisvorschlag speichern') }}</button>
                </div>
                @error('offer_price')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('Der Eigentümer sieht deinen Vorschlag. Leer lassen, wenn du keinen Preis nennen möchtest.') }}</div>
            </form>
        @else
            <form method="POST" action="{{ route('records.interest', $record) }}">
                @csrf
                <input type="hidden" name="interested" value="1">
                <p class="text-body-secondary mb-2">{{ __('Möchtest du diese Platte haben?') }}</p>
                <label for="offer_price" class="form-label">{{ __('Dein Preisvorschlag') }} <span class="text-body-secondary small">({{ __('optional') }})</span></label>
                <div class="d-flex flex-wrap gap-2">
                    <div class="input-group" style="max-width: 12rem">
                        <input type="text" inputmode="decimal" name="offer_price" id="offer_price" class="form-control @error('offer_price') is-invalid @enderror"
                            value="{{ old('offer_price') }}">
                        <span class="input-group-text">€</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-star-fill"></i> {{ __('Interesse anmelden') }}</button>
                </div>
                @error('offer_price')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            </form>
        @endif
    </div>
</div>

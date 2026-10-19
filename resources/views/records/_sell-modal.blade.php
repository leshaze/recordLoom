{{-- Dialog to sell $record to $seller (sale price starts with the seller's offer, else the current price).
     $others: the other interested sellers. $fromSellerPage: go back to the page of the seller after the sale. --}}
@php $id = 'sell-modal-'.$record->id.'-'.$seller->id; @endphp
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('records.sell', $record) }}" class="modal-content">
            @csrf
            <input type="hidden" name="seller_id" value="{{ $seller->id }}">
            @if ($fromSellerPage ?? false)
                <input type="hidden" name="from" value="seller">
            @endif
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="{{ $id }}-title">{{ __('Verkaufen an :name', ['name' => $seller->name]) }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Schließen') }}"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">{{ $record->artist->name }} – {{ $record->title }}</p>
                <div class="row g-3">
                    <div class="col-6">
                        <label for="{{ $id }}-price" class="form-label">{{ __('Verkaufspreis') }}</label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" name="sold_price" id="{{ $id }}-price" class="form-control"
                                value="{{ \App\Support\Format::input($seller->pivot->offer_price ?? $record->current_price) }}">
                            <span class="input-group-text">€</span>
                        </div>
                        <div class="form-text">{{ $seller->pivot->offer_price !== null ? __('Preisvorschlag von :name', ['name' => $seller->name]) : __('Aktueller Preis') }}</div>
                    </div>
                    <div class="col-6">
                        <label for="{{ $id }}-date" class="form-label">{{ __('Verkaufsdatum') }}</label>
                        <input type="date" name="sold_on" id="{{ $id }}-date" class="form-control" required value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">
                    {{ __('Die Platte wird als verkauft markiert und :name benachrichtigt.', ['name' => $seller->name]) }}
                    @if ($others->isNotEmpty())
                        {{ __('Diese Interessenten erfahren, dass sie die Platte nicht bekommen: :names.', ['names' => $others->pluck('name')->implode(', ')]) }}
                    @endif
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Abbrechen') }}</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-bag-check"></i> {{ __('Verkaufen') }}</button>
            </div>
        </form>
    </div>
</div>

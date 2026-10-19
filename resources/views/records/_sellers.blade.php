{{-- Owner and admin: sellers the record is assigned to, their interest, and selling to one of them. --}}
<div class="card mb-3 border-warning-subtle">
    <div class="card-header"><i class="bi bi-people"></i> {{ __('Verkäufer & Interesse') }}</div>
    <div class="card-body">
        @if ($record->sold && $record->soldToUser)
            <p class="mb-2 text-success"><i class="bi bi-bag-check"></i>
                {{ __('An :name verkauft am :date.', ['name' => $record->soldToUser->name, 'date' => \App\Support\Format::date($record->sold_on)]) }}</p>
        @endif

        @if ($sellers->isEmpty())
            <p class="text-body-secondary mb-2">{{ __('Die Platte ist noch keinem Verkäufer zugewiesen.') }}</p>
        @else
            <ul class="list-group mb-3">
                @foreach ($sellers as $seller)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold">{{ $seller->name }}</span>
                        @if ($seller->pivot->interested_at)
                            <span class="badge text-bg-warning"><i class="bi bi-star-fill"></i>
                                {{ __('Interesse seit :date', ['date' => \App\Support\Format::date($seller->pivot->interested_at)]) }}</span>
                        @else
                            <span class="small text-body-secondary">{{ __('kein Interesse angemeldet') }}</span>
                        @endif
                        @if ($record->sold_to_user_id === $seller->id)
                            <span class="badge text-bg-success">{{ __('Käufer') }}</span>
                        @endif
                        @if (! $record->sold && $seller->pivot->interested_at)
                            <button type="button" class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#sell-modal-{{ $seller->id }}">
                                <i class="bi bi-bag-check"></i> {{ __('Verkaufen an :name', ['name' => $seller->name]) }}
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($availableSellers->isEmpty())
            <p class="small text-body-secondary mb-0">{{ __('Es gibt noch keine Verkäufer. Ein Admin kann sie unter „Verwaltung → Benutzer“ einladen.') }}</p>
        @else
            <details @if ($sellers->isEmpty()) open @endif>
                <summary class="small">{{ __('Verkäufer zuweisen') }}</summary>
                <form method="POST" action="{{ route('records.sellers', $record) }}" class="mt-2">
                    @csrf
                    @method('PUT')
                    <div class="d-flex flex-wrap column-gap-3 row-gap-1 mb-2">
                        @foreach ($availableSellers as $seller)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sellers[]" value="{{ $seller->id }}" id="seller-{{ $seller->id }}"
                                    @checked($sellers->contains('id', $seller->id))>
                                <label class="form-check-label" for="seller-{{ $seller->id }}">{{ $seller->name }}</label>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Zuweisung speichern') }}</button>
                </form>
            </details>
        @endif
    </div>
</div>

@if (! $record->sold)
    @foreach ($sellers->filter(fn ($seller) => $seller->pivot->interested_at) as $seller)
        <div class="modal fade" id="sell-modal-{{ $seller->id }}" tabindex="-1" aria-labelledby="sell-modal-{{ $seller->id }}-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('records.sell', $record) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="seller_id" value="{{ $seller->id }}">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="sell-modal-{{ $seller->id }}-title">{{ __('Verkaufen an :name', ['name' => $seller->name]) }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Schließen') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label for="sold_price-{{ $seller->id }}" class="form-label">{{ __('Verkaufspreis') }}</label>
                                <div class="input-group">
                                    <input type="text" inputmode="decimal" name="sold_price" id="sold_price-{{ $seller->id }}" class="form-control"
                                        value="{{ $record->current_price !== null ? str_replace('.', app()->getLocale() === 'de' ? ',' : '.', $record->current_price) : '' }}">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="sold_on-{{ $seller->id }}" class="form-label">{{ __('Verkaufsdatum') }}</label>
                                <input type="date" name="sold_on" id="sold_on-{{ $seller->id }}" class="form-control" required value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        @php $others = $sellers->filter(fn ($other) => $other->pivot->interested_at && $other->id !== $seller->id); @endphp
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
    @endforeach
@endif

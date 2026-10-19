{{-- Owner and admin: sellers the record is assigned to, their interest and offers, and selling to one of them. --}}
@php
    // The highest offer is marked when more than one seller made an offer.
    $offers = $sellers->filter(fn ($seller) => $seller->pivot->interested_at && $seller->pivot->offer_price !== null);
    $bestOffer = $offers->count() > 1 ? $offers->max(fn ($seller) => (float) $seller->pivot->offer_price) : null;
@endphp
<div class="card mb-3 border-warning-subtle">
    <div class="card-header"><i class="bi bi-people"></i> {{ __('Verkäufer & Interesse') }}</div>
    <div class="card-body">
        @unless ($record->sold)
            <form method="POST" action="{{ route('records.selling', $record) }}" class="mb-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="selling" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="selling" value="1" id="selling-switch"
                        @checked($record->selling) data-auto-submit
                        @if ($record->selling && $sellers->contains(fn ($seller) => $seller->pivot->interested_at))
                            data-confirm-off="{{ __('Alle Interessenten und ihre Preisvorschläge werden gelöscht und benachrichtigt, dass die Platte nicht mehr verkauft wird. Fortfahren?') }}"
                        @endif>
                    <label class="form-check-label" for="selling-switch">
                        {{ __('Zum Verkauf vormerken') }}
                        <span class="small text-body-secondary d-block">{{ __('Die Platte ist dann für alle Verkäufer sichtbar und jeder kann Interesse anmelden.') }}</span>
                    </label>
                </div>
                <noscript><button type="submit" class="btn btn-sm btn-outline-primary mt-2">{{ __('Speichern') }}</button></noscript>
            </form>
        @endunless

        @if ($record->sold && $record->soldToUser)
            <p class="mb-2 text-success"><i class="bi bi-bag-check"></i>
                {{ __('An :name verkauft am :date.', ['name' => $record->soldToUser->name, 'date' => \App\Support\Format::date($record->sold_on)]) }}</p>
        @endif

        @if ($sellers->isEmpty())
            <p class="text-body-secondary mb-2">
                {{ $record->selling && ! $record->sold ? __('Noch kein Verkäufer hat Interesse angemeldet.') : __('Die Platte ist noch keinem Verkäufer zugewiesen.') }}
            </p>
        @else
            <ul class="list-group mb-3">
                @foreach ($sellers as $seller)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('sellers.show', $seller) }}" class="fw-semibold">{{ $seller->name }}</a>
                        @unless ($seller->pivot->assigned)
                            <span class="small text-body-secondary">({{ __('nicht zugewiesen') }})</span>
                        @endunless
                        @if ($seller->pivot->interested_at)
                            <span class="badge text-bg-warning"><i class="bi bi-star-fill"></i>
                                {{ __('Interesse seit :date', ['date' => \App\Support\Format::date($seller->pivot->interested_at)]) }}</span>
                            @if ($seller->pivot->offer_price !== null)
                                <span class="badge {{ $bestOffer !== null && (float) $seller->pivot->offer_price === $bestOffer ? 'text-bg-success' : 'text-bg-light border' }}">
                                    {{ __('bietet :price', ['price' => \App\Support\Format::euro($seller->pivot->offer_price)]) }}
                                    @if ($bestOffer !== null && (float) $seller->pivot->offer_price === $bestOffer)
                                        · {{ __('höchstes Gebot') }}
                                    @endif
                                </span>
                            @elseif (! $record->sold)
                                <span class="small text-body-secondary">{{ __('ohne Preisvorschlag') }}</span>
                            @endif
                        @else
                            <span class="small text-body-secondary">{{ __('kein Interesse angemeldet') }}</span>
                        @endif
                        @if ($record->sold_to_user_id === $seller->id)
                            <span class="badge text-bg-success">{{ __('Käufer') }}</span>
                        @endif
                        @if (! $record->sold && $seller->pivot->interested_at)
                            <button type="button" class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#sell-modal-{{ $record->id }}-{{ $seller->id }}">
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
            <details @if ($sellers->isEmpty() && ! $record->selling) open @endif>
                <summary class="small">{{ __('Verkäufer gezielt zuweisen') }}</summary>
                <p class="small text-body-secondary mt-2 mb-0">{{ __('Zugewiesene Verkäufer sehen die Platte auch, wenn sie nicht zum Verkauf vorgemerkt ist.') }}</p>
                <form method="POST" action="{{ route('records.sellers', $record) }}" class="mt-2">
                    @csrf
                    @method('PUT')
                    <div class="d-flex flex-wrap column-gap-3 row-gap-1 mb-2">
                        @foreach ($availableSellers as $seller)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sellers[]" value="{{ $seller->id }}" id="seller-{{ $seller->id }}"
                                    @checked($sellers->contains(fn ($assigned) => $assigned->id === $seller->id && $assigned->pivot->assigned))>
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
        @include('records._sell-modal', ['others' => $sellers->filter(fn ($other) => $other->pivot->interested_at && $other->id !== $seller->id)])
    @endforeach
@endif

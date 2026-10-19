{{-- The records a seller is interested in, with the offer compared to the other sellers, and selling. --}}
<x-app-layout :title="$seller->name">
    <div class="container-fluid px-lg-4">
        <a href="{{ route('sellers.index') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-people"></i> {{ __('Alle Verkäufer') }}</a>
        <h1 class="h3 mb-0">{{ $seller->name }}</h1>
        <div class="text-body-secondary mb-3">
            {{ trans_choice(':count Platte mit Interesse|:count Platten mit Interesse', $rows->count()) }}
            @if ($offerSum > 0) · {{ __('Preisvorschläge') }} {{ \App\Support\Format::euro($offerSum) }} @endif
            @if ($valueSum > 0) · {{ __('Aktueller Wert') }} {{ \App\Support\Format::euro($valueSum) }} @endif
        </div>

        @if ($rows->isEmpty())
            <div class="card card-body text-body-secondary">{{ __(':name hat zurzeit kein Interesse an deinen Platten angemeldet.', ['name' => $seller->name]) }}</div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Platte') }}</th>
                                <th class="text-end">{{ __('Aktueller Preis') }}</th>
                                <th>{{ __('Preisvorschlag') }}</th>
                                <th>{{ __('Weitere Interessenten') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as ['record' => $record, 'seller' => $own, 'others' => $others, 'best' => $best])
                                @php
                                    $offer = $own->pivot->offer_price;
                                    $bestOffer = $best ? (float) $best->pivot->offer_price : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <x-cover :record="$record" :size="40" class="flex-shrink-0" />
                                            <div>
                                                <a href="{{ route('records.show', $record) }}" class="fw-semibold">{{ $record->title }}</a>
                                                <div class="small text-body-secondary">{{ $record->artist->name }} · {{ $record->kind }}
                                                    · {{ __('Interesse seit :date', ['date' => \App\Support\Format::date($own->pivot->interested_at)]) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Format::euro($record->current_price) }}</td>
                                    <td>
                                        @if ($offer === null)
                                            <span class="small text-body-secondary">{{ __('ohne Preisvorschlag') }}</span>
                                        @elseif ($bestOffer === null || (float) $offer > $bestOffer)
                                            <span class="badge text-bg-success">{{ \App\Support\Format::euro($offer) }}
                                                @if ($bestOffer !== null) · {{ __('höchstes Gebot') }} @endif</span>
                                        @elseif ((float) $offer === $bestOffer)
                                            <span class="badge text-bg-info">{{ \App\Support\Format::euro($offer) }} · {{ __('gleich hoch wie :name', ['name' => $best->name]) }}</span>
                                        @else
                                            <span class="badge text-bg-danger">{{ \App\Support\Format::euro($offer) }}</span>
                                            <div class="small text-danger">{{ __(':name bietet mehr: :price', ['name' => $best->name, 'price' => \App\Support\Format::euro($bestOffer)]) }}</div>
                                        @endif
                                        @if ($offer === null && $bestOffer !== null)
                                            <div class="small text-body-secondary">{{ __(':name bietet :price', ['name' => $best->name, 'price' => \App\Support\Format::euro($bestOffer)]) }}</div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @forelse ($others as $other)
                                            <div class="text-nowrap"><a href="{{ route('sellers.show', $other) }}">{{ $other->name }}</a>
                                                <span class="text-body-secondary">{{ $other->pivot->offer_price !== null ? \App\Support\Format::euro($other->pivot->offer_price) : __('ohne Preisvorschlag') }}</span></div>
                                        @empty
                                            <span class="text-body-secondary">–</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end">
                                        @can('update', $record)
                                            <button type="button" class="btn btn-sm btn-success text-nowrap" data-bs-toggle="modal" data-bs-target="#sell-modal-{{ $record->id }}-{{ $own->id }}">
                                                <i class="bi bi-bag-check"></i> {{ __('Verkaufen') }}
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @foreach ($rows as ['record' => $record, 'seller' => $own, 'others' => $others])
                @can('update', $record)
                    @include('records._sell-modal', ['seller' => $own, 'fromSellerPage' => true])
                @endcan
            @endforeach
        @endif
    </div>
</x-app-layout>

<x-app-layout :title="$record->title">
    @php
        $field = fn ($value) => filled($value) ? $value : '–';
    @endphp
    <div class="container-fluid px-lg-4">
        {{-- Paging through the last used list (filter and sorting are kept) --}}
        <nav class="d-flex flex-wrap align-items-center gap-2 mb-2 small" aria-label="{{ __('Blättern') }}">
            <a href="{{ $navigation['list'] }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul"></i> {{ __('Zur Liste') }}</a>
            @if ($navigation['position'])
                <div class="btn-group btn-group-sm ms-auto">
                    <a @class(['btn btn-outline-secondary', 'disabled' => ! $navigation['previous']]) id="record-previous"
                        href="{{ $navigation['previous'] ? route('records.show', $navigation['previous']) : '#' }}"
                        title="{{ __('Vorige Platte') }} (←)" aria-label="{{ __('Vorige Platte') }}"><i class="bi bi-chevron-left"></i></a>
                    <span class="btn btn-outline-secondary disabled text-body">{{ __(':position von :total', ['position' => $navigation['position'], 'total' => $navigation['total']]) }}</span>
                    <a @class(['btn btn-outline-secondary', 'disabled' => ! $navigation['next']]) id="record-next"
                        href="{{ $navigation['next'] ? route('records.show', $navigation['next']) : '#' }}"
                        title="{{ __('Nächste Platte') }} (→)" aria-label="{{ __('Nächste Platte') }}"><i class="bi bi-chevron-right"></i></a>
                </div>
            @endif
        </nav>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div style="min-width: 0;">
                <h1 class="h3 mb-0 text-break">{{ $record->title }}</h1>
                <div class="text-body-secondary">
                    <a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a>
                    · {{ $record->kind }}
                    @if ($record->release_year) · {{ $record->release_year }} @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('records.edit', $record) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> {{ __('Bearbeiten') }}</a>
                <x-delete-button :action="route('records.destroy', $record)" :label="__('Löschen')"
                    :message="__('Die Platte „:title“ von :artist wirklich löschen?', ['title' => $record->title, 'artist' => $record->artist->name])" />
            </div>
        </div>

        <div class="row g-3">
            <div class="col-5 col-md-4 col-lg-3">
                <x-cover :record="$record" :full="true" />
                <div class="mt-2 d-none d-md-flex flex-wrap gap-1">
                    @include('records._status-badges')
                </div>
            </div>

            {{-- Summary next to the cover on phones; on larger screens the cards below show it --}}
            <div class="col-7 d-md-none small">
                <div class="text-body-secondary">{{ __('Aktueller Preis') }}</div>
                <div class="fs-5 fw-semibold mb-2">{{ \App\Support\Format::euro($record->current_price) ?: '–' }}</div>
                @if ($record->gradingMedia() || $record->gradingCover())
                    <div class="mb-2">
                        <span class="text-body-secondary">{{ __('M') }}</span> <x-grading-badge :grading="$record->gradingMedia()" />
                        <span class="text-body-secondary ms-1">{{ __('C') }}</span> <x-grading-badge :grading="$record->gradingCover()" />
                    </div>
                @endif
                <div class="d-flex flex-wrap gap-1">
                    @include('records._status-badges')
                </div>
            </div>

            <div class="col-md-8 col-lg-9">
                <div class="card mb-3">
                    <div class="card-header">{{ __('Stammdaten & Details') }}</div>
                    <div class="card-body"><dl class="row mb-0">
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Label') }}</dt>
                        <dd class="col-7 col-sm-9"><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Katalog-Nr.') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->catalog_number) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Matrix-Nr.') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->matrix_number) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Barcode') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->barcode) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Archiv-Nr.') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->archive_number) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Herkunftsland') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->country?->name) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Erscheinungsjahr') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->release_year) }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Neuauflage') }}</dt>
                        <dd class="col-7 col-sm-9">{{ $field($record->reissue_year) }}</dd>
                    </dl></div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">{{ __('Zustand & Preis') }}</div>
                    <div class="card-body"><dl class="row mb-0">
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Grading Media') }}</dt>
                        <dd class="col-7 col-sm-9">
                            @if ($record->gradingMedia())
                                <x-grading-badge :grading="$record->gradingMedia()" /> {{ $record->gradingMedia()['label'] }}
                            @else – @endif
                        </dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Grading Cover') }}</dt>
                        <dd class="col-7 col-sm-9">
                            @if ($record->gradingCover())
                                <x-grading-badge :grading="$record->gradingCover()" /> {{ $record->gradingCover()['label'] }}
                            @else – @endif
                        </dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Aktueller Preis') }}</dt>
                        <dd class="col-7 col-sm-9">{{ \App\Support\Format::euro($record->current_price) ?: '–' }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Kaufpreis') }}</dt>
                        <dd class="col-7 col-sm-9">{{ \App\Support\Format::euro($record->buy_price) ?: '–' }}</dd>
                        <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Anbieter') }}</dt>
                        <dd class="col-7 col-sm-9">
                            @if ($record->platform)
                                <a href="{{ route('platforms.show', $record->platform) }}">{{ $record->platform->name }}</a>
                            @else – @endif
                        </dd>
                    </dl></div>
                </div>

                <div class="card mb-3 border-info-subtle">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span><i class="bi bi-vinyl"></i> Discogs</span>
                        @if ($record->discogsUrl())
                            <a href="{{ $record->discogsUrl() }}" target="_blank" rel="noopener noreferrer" class="small">{{ __('Auf Discogs ansehen') }} <i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (! $record->discogs_release_id)
                            <p class="mb-2">{{ __('Diese Platte ist noch nicht mit Discogs verknüpft.') }}</p>
                            <a href="{{ route('records.edit', $record) }}" class="btn btn-sm btn-outline-info">{{ __('Im Formular bei Discogs suchen') }}</a>
                            <a href="{{ route('discogs.match') }}" class="btn btn-sm btn-outline-secondary">{{ __('Bestand abgleichen') }}</a>
                        @else
                            @php
                                $suggested = $record->discogsSuggestedPrice();
                                $suggestions = collect($record->discogs_price_suggestions ?? [])->pluck('value')->filter(fn ($value) => $value !== null);
                                $currency = $record->discogs_currency;
                                $money = fn ($value) => $value === null ? '–' : (($currency ?? 'EUR') === 'EUR' ? \App\Support\Format::euro($value) : number_format((float) $value, 2).' '.$currency);
                            @endphp
                            @if ($record->discogs_prices_updated_at)
                                <div class="row g-3 mb-2">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="small text-body-secondary">{{ __('Günstigstes Angebot auf Discogs') }}</div>
                                            @if ($record->discogs_lowest_price !== null)
                                                <div class="fs-4 fw-semibold">{{ $money($record->discogs_lowest_price) }}</div>
                                                <div class="small text-body-secondary mb-2">{{ trans_choice(':count Angebot|:count Angebote', (int) $record->discogs_num_for_sale) }}</div>
                                                <form method="POST" action="{{ route('discogs.apply-price', $record) }}">
                                                    @csrf
                                                    <input type="hidden" name="source" value="lowest">
                                                    <button type="submit" class="btn btn-sm btn-outline-info">{{ __('Als aktuellen Preis übernehmen') }}</button>
                                                </form>
                                            @else
                                                <div class="text-body-secondary">{{ __('Zurzeit keine Angebote.') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="small text-body-secondary">{{ __('Preisvorschlag von Discogs') }}</div>
                                            @if ($suggestions->isNotEmpty())
                                                <div class="fs-4 fw-semibold">{{ $money($suggestions->min()) }} – {{ $money($suggestions->max()) }}</div>
                                                <div class="small text-body-secondary mb-2">{{ __('je nach Zustand') }}</div>
                                                @if ($suggested)
                                                    <div class="mb-2">{{ __('Für deinen Zustand (:condition):', ['condition' => $record->discogsCondition()]) }}
                                                        <strong>{{ $money($suggested['value']) }}</strong></div>
                                                    <form method="POST" action="{{ route('discogs.apply-price', $record) }}">
                                                        @csrf
                                                        <input type="hidden" name="source" value="suggestion">
                                                        <button type="submit" class="btn btn-sm btn-outline-info">{{ __('Als aktuellen Preis übernehmen') }}</button>
                                                    </form>
                                                @else
                                                    <div class="small text-body-secondary">{{ __('Setze Grading Media, um den Vorschlag für deinen Zustand zu sehen.') }}</div>
                                                @endif
                                                <details class="small mt-2">
                                                    <summary>{{ __('Alle Preisvorschläge') }}</summary>
                                                    <table class="table table-sm small mt-2 mb-0">
                                                        @foreach ($record->discogs_price_suggestions as $condition => $price)
                                                            <tr @class(['fw-semibold' => $condition === $record->discogsCondition()])>
                                                                <td>{{ $condition }}</td>
                                                                <td class="text-end">{{ $money($price['value'] ?? null) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                </details>
                                            @else
                                                <div class="small text-body-secondary">{{ $record->discogs_suggestions_note ?? __('Discogs hat für diese Pressung keine Preisvorschläge.') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <p class="small text-body-secondary mb-2">
                                    {{ __('Stand') }}: {{ \App\Support\Format::date($record->discogs_prices_updated_at) }} {{ $record->discogs_prices_updated_at->format('H:i') }}.
                                    {{ __('Das teuerste Angebot und Verkaufspreise stellt Discogs über die Schnittstelle nicht bereit. Übernommene Preise erscheinen in der Preisentwicklung mit dem Anbieter „Discogs“.') }}
                                </p>
                            @else
                                <p class="text-body-secondary">{{ __('Noch keine Marktdaten geladen.') }}</p>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('discogs.prices', $record) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info"><i class="bi bi-arrow-repeat"></i> {{ __('Marktdaten aktualisieren') }}</button>
                                </form>
                                <form method="POST" action="{{ route('discogs.unlink', $record) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Verknüpfung entfernen') }}</button>
                                </form>
                            </div>
                        @endif
                        <div class="small text-body-secondary mt-2">{{ __('Daten von') }} <a href="https://www.discogs.com" target="_blank" rel="noopener noreferrer">Discogs</a></div>
                    </div>
                </div>

                @if ($record->sold)
                    <div class="card mb-3">
                        <div class="card-header">{{ __('Verkauf') }}</div>
                        <div class="card-body"><dl class="row mb-0">
                            <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Verkaufsdatum') }}</dt>
                            <dd class="col-7 col-sm-9">{{ \App\Support\Format::date($record->sold_on) ?: '–' }}</dd>
                            <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Verkauft an') }}</dt>
                            <dd class="col-7 col-sm-9">{{ $field($record->sold_to) }}</dd>
                            <dt class="col-5 col-sm-3 fw-normal text-body-secondary">{{ __('Verkaufspreis') }}</dt>
                            <dd class="col-7 col-sm-9">{{ \App\Support\Format::euro($record->sold_price) ?: '–' }}</dd>
                        </dl></div>
                    </div>
                @endif

                @if ($record->note)
                    <div class="card mb-3">
                        <div class="card-header">{{ __('Notiz') }}</div>
                        <div class="card-body" style="white-space: pre-line;">{{ $record->note }}</div>
                    </div>
                @endif

                @if ($prices->count() >= 2)
                    <div class="card mb-3">
                        <div class="card-header">{{ __('Preisentwicklung') }}</div>
                        <div class="card-body"><div class="row mb-0">
                            <div class="col-md-5 small">
                                @foreach ($prices as $price)
                                    {{ \App\Support\Format::date($price->created_at) }} – {{ \App\Support\Format::euro($price->price) }}
                                    @if ($price->platform)
                                        – @if ($price->platform->safe_url)<a href="{{ $price->platform->safe_url }}" target="_blank" rel="noopener noreferrer">{{ $price->platform->name }}</a>@else{{ $price->platform->name }}@endif
                                    @endif
                                    <br>
                                @endforeach
                            </div>
                            <div class="col-md-7">
                                <canvas id="priceChart"></canvas>
                            </div>
                        </div></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($prices->count() >= 2)
        <script type="module">
            new Chart(document.getElementById('priceChart'), {
                type: 'line',
                data: {
                    labels: @json($prices->map(fn ($price) => \App\Support\Format::date($price->created_at))->values()),
                    datasets: [{
                        label: @js(__('Preis in €')),
                        backgroundColor: 'rgb(255, 99, 132)',
                        borderColor: 'rgb(255, 99, 132)',
                        data: @json($prices->map(fn ($price) => (float) $price->price)->values())
                    }]
                },
                options: { plugins: { legend: { display: false } } }
            });
        </script>
    @endif

    @if ($navigation['position'])
        <script type="module">
            // ← / → page through the list, unless a field has the focus or a dialog is open.
            document.addEventListener('keydown', (event) => {
                if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
                if (event.target.closest('input, textarea, select, [contenteditable]') || document.querySelector('.modal.show')) return;
                const link = document.getElementById(event.key === 'ArrowLeft' ? 'record-previous' : event.key === 'ArrowRight' ? 'record-next' : '');
                if (link && ! link.classList.contains('disabled')) {
                    window.location = link.href;
                }
            });
        </script>
    @endif
</x-app-layout>

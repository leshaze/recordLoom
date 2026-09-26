<x-app-layout :title="$record->title">
    @php
        $field = fn ($value) => filled($value) ? $value : '–';
    @endphp
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">{{ $record->title }}</h1>
                <div class="text-body-secondary">
                    <a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a>
                    · {{ $record->kind }}
                    @if ($record->release_year) · {{ $record->release_year }} @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('records.edit', $record) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Bearbeiten</a>
                <x-delete-button :action="route('records.destroy', $record)" label="Löschen"
                    :message="'Die Platte „'.$record->title.'“ von '.$record->artist->name.' wirklich löschen?'" />
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4 col-lg-3">
                <x-cover :record="$record" :full="true" />
                <div class="mt-2 d-flex flex-wrap gap-1">
                    @if ($record->sold) <span class="badge text-bg-secondary">Verkauft</span> @endif
                    @if ($record->lost) <span class="badge text-bg-danger">Verloren</span> @endif
                    @if ($record->selling && ! $record->sold) <span class="badge text-bg-info">Zum Verkauf vorgemerkt</span> @endif
                    @foreach ($record->editions as $edition)
                        <a href="{{ route('records.index', ['edition' => $edition->id]) }}" class="badge text-bg-dark border text-decoration-none">{{ $edition->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="col-md-8 col-lg-9">
                <div class="card mb-3">
                    <div class="card-header">Stammdaten &amp; Details</div>
                    <dl class="card-body row mb-0">
                        <dt class="col-sm-3">Label</dt>
                        <dd class="col-sm-9"><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></dd>
                        <dt class="col-sm-3">Katalog-Nr.</dt>
                        <dd class="col-sm-9">{{ $field($record->catalog_number) }}</dd>
                        <dt class="col-sm-3">Matrix-Nr.</dt>
                        <dd class="col-sm-9">{{ $field($record->matrix_number) }}</dd>
                        <dt class="col-sm-3">Barcode</dt>
                        <dd class="col-sm-9">{{ $field($record->barcode) }}</dd>
                        <dt class="col-sm-3">Archiv-Nr.</dt>
                        <dd class="col-sm-9">{{ $field($record->archive_number) }}</dd>
                        <dt class="col-sm-3">Herkunftsland</dt>
                        <dd class="col-sm-9">{{ $field($record->country?->name) }}</dd>
                        <dt class="col-sm-3">Erscheinungsjahr</dt>
                        <dd class="col-sm-9">{{ $field($record->release_year) }}</dd>
                        <dt class="col-sm-3">Neuauflage</dt>
                        <dd class="col-sm-9">{{ $field($record->reissue_year) }}</dd>
                    </dl>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Zustand &amp; Preis</div>
                    <dl class="card-body row mb-0">
                        <dt class="col-sm-3">Grading Media</dt>
                        <dd class="col-sm-9">
                            @if ($record->gradingMedia())
                                <x-grading-badge :grading="$record->gradingMedia()" /> {{ $record->gradingMedia()['label'] }}
                            @else – @endif
                        </dd>
                        <dt class="col-sm-3">Grading Cover</dt>
                        <dd class="col-sm-9">
                            @if ($record->gradingCover())
                                <x-grading-badge :grading="$record->gradingCover()" /> {{ $record->gradingCover()['label'] }}
                            @else – @endif
                        </dd>
                        <dt class="col-sm-3">Aktueller Preis</dt>
                        <dd class="col-sm-9">{{ \App\Support\Format::euro($record->current_price) ?: '–' }}</dd>
                        <dt class="col-sm-3">Kaufpreis</dt>
                        <dd class="col-sm-9">{{ \App\Support\Format::euro($record->buy_price) ?: '–' }}</dd>
                        <dt class="col-sm-3">Anbieter</dt>
                        <dd class="col-sm-9">
                            @if ($record->platform)
                                <a href="{{ route('platforms.show', $record->platform) }}">{{ $record->platform->name }}</a>
                            @else – @endif
                        </dd>
                    </dl>
                </div>

                @if ($record->sold)
                    <div class="card mb-3">
                        <div class="card-header">Verkauf</div>
                        <dl class="card-body row mb-0">
                            <dt class="col-sm-3">Verkaufsdatum</dt>
                            <dd class="col-sm-9">{{ $record->sold_on?->format('d.m.Y') ?? '–' }}</dd>
                            <dt class="col-sm-3">Verkauft an</dt>
                            <dd class="col-sm-9">{{ $field($record->sold_to) }}</dd>
                            <dt class="col-sm-3">Verkaufspreis</dt>
                            <dd class="col-sm-9">{{ \App\Support\Format::euro($record->sold_price) ?: '–' }}</dd>
                        </dl>
                    </div>
                @endif

                @if ($record->note)
                    <div class="card mb-3">
                        <div class="card-header">Notiz</div>
                        <div class="card-body" style="white-space: pre-line;">{{ $record->note }}</div>
                    </div>
                @endif

                @if ($prices->count() >= 2)
                    <div class="card mb-3">
                        <div class="card-header">Preisentwicklung</div>
                        <div class="card-body row">
                            <div class="col-md-5 small">
                                @foreach ($prices as $price)
                                    {{ $price->created_at->format('d.m.Y') }} – {{ \App\Support\Format::euro($price->price) }}
                                    @if ($price->platform)
                                        – @if ($price->platform->safe_url)<a href="{{ $price->platform->safe_url }}" target="_blank" rel="noopener noreferrer">{{ $price->platform->name }}</a>@else{{ $price->platform->name }}@endif
                                    @endif
                                    <br>
                                @endforeach
                            </div>
                            <div class="col-md-7">
                                <canvas id="priceChart"></canvas>
                            </div>
                        </div>
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
                    labels: @json($prices->map(fn ($price) => $price->created_at->format('d.m.y'))->values()),
                    datasets: [{
                        label: 'Preis in €',
                        backgroundColor: 'rgb(255, 99, 132)',
                        borderColor: 'rgb(255, 99, 132)',
                        data: @json($prices->map(fn ($price) => (float) $price->price)->values())
                    }]
                },
                options: { plugins: { legend: { display: false } } }
            });
        </script>
    @endif
</x-app-layout>

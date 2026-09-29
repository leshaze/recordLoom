<x-app-layout title="{{ __('Übersicht') }}">
    <div class="container-fluid px-lg-4">
        <h1 class="h3 mb-4">{{ __('Willkommen bei RecordLoom') }}</h1>

        @php
            $tiles = [
                [__('Platten im Bestand'), $count, 'bi-vinyl', route('records.index', ['status' => 'available'])],
                [__('Wert im Bestand'), \App\Support\Format::euro($total_value ?: 0), 'bi-cash-coin', null],
                [__('LPs'), $count_lp, 'bi-disc', route('records.index', ['kind' => 'LP', 'status' => 'available'])],
                [__('CDs'), $count_cd, 'bi-disc-fill', route('records.index', ['kind' => 'CD', 'status' => 'available'])],
                [__('Zum Verkauf vorgemerkt'), $count_selling, 'bi-tag', route('records.index', ['status' => 'selling'])],
                [__('Verkauft'), $count_sold, 'bi-bag-check', route('records.index', ['status' => 'sold'])],
                [__('Künstler (Menü)'), $count_artist, 'bi-person', route('artists.index')],
                [__('Labels'), $count_label, 'bi-building', route('labels.index')],
            ];
        @endphp

        <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
            @foreach ($tiles as [$label, $value, $icon, $url])
                <div class="col">
                    <div class="card h-100 position-relative">
                        <div class="card-body">
                            <div class="text-body-secondary small"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
                            <div class="fs-3 fw-semibold">{{ $value }}</div>
                            @if ($url)
                                <a href="{{ $url }}" class="stretched-link" aria-label="{{ __(':label anzeigen', ['label' => $label]) }}"></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">{{ __('Wert im Bestand pro Monat') }}</div>
                    <div class="card-body">
                        @if (count($value_by_month) >= 2)
                            <div style="position: relative; height: 18rem;">
                                <canvas id="valueChart" role="img" aria-label="{{ __('Wert im Bestand pro Monat') }}"></canvas>
                            </div>
                            <p class="small text-body-secondary mb-0 mt-2">
                                {{ __('Jede Platte zählt ab dem Tag, an dem sie erfasst wurde, bis zum Verkaufsdatum, mit dem Preis, der damals in der Preisentwicklung stand.') }}
                            </p>
                        @else
                            <p class="text-body-secondary mb-0">{{ __('Der Verlauf erscheint, sobald Platten aus mindestens zwei Monaten erfasst sind.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Zuletzt hinzugefügt') }}</span>
                    <a href="{{ route('records.index', ['sort' => 'created', 'dir' => 'desc']) }}" class="small">{{ __('Alle anzeigen') }}</a>
                </div>
                @if ($latest->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach ($latest as $record)
                            <a href="{{ route('records.show', $record) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <x-cover :record="$record" :size="40" class="flex-shrink-0" />
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="fw-semibold text-truncate">{{ $record->title }}</div>
                                    <div class="small text-body-secondary text-truncate">{{ $record->artist->name }} · {{ $record->kind }}@if ($record->release_year) · {{ $record->release_year }}@endif</div>
                                </div>
                                <span class="small text-body-secondary text-nowrap">{{ \App\Support\Format::date($record->created_at) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="card-body">{{ __('Noch keine Platten erfasst.') }} <a href="{{ route('records.create') }}">{{ __('Erste Platte anlegen') }}</a></div>
                @endif
            </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            @foreach ([[__('Top 5 Künstler nach Wert'), $top_artists, 'artists'], [__('Top 5 Labels nach Wert'), $top_labels, 'labels']] as [$heading, $items, $resource])
                <div class="col">
                    <div class="card h-100">
                        <div class="card-header">{{ $heading }}</div>
                        @if ($items->isNotEmpty())
                            <ol class="list-group list-group-flush">
                                @foreach ($items as $item)
                                    <li class="list-group-item d-flex gap-2 align-items-baseline">
                                        <span class="text-body-secondary text-nowrap flex-shrink-0">{{ $loop->iteration }}.</span>
                                        <a href="{{ route($resource.'.show', $item) }}" class="text-break flex-grow-1">{{ $item->name }}</a>
                                        <span class="small text-body-secondary text-nowrap">{{ trans_choice(':count Platte|:count Platten', $item->count) }}</span>
                                        <span class="text-nowrap">{{ \App\Support\Format::euro($item->value) }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="card-body text-body-secondary">{{ __('Noch keine Preise erfasst.') }}</div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="col">
                <div class="card h-100">
                    <div class="card-header">{{ __('Grading Media im Bestand') }}</div>
                    <div class="card-body">
                        @php $maxGrading = max(array_column($gradings, 'count') ?: [1]); @endphp
                        @forelse ($gradings as $row)
                            <div class="d-flex align-items-center gap-2 mb-2 small">
                                <span class="text-nowrap" style="width: 4.5rem;">
                                    @if ($row['grading'])
                                        <x-grading-badge :grading="$row['grading']" />
                                    @else
                                        <span class="text-body-secondary">{{ __('ohne') }}</span>
                                    @endif
                                </span>
                                <div class="progress flex-grow-1" role="progressbar" style="height: .5rem;"
                                    aria-label="{{ $row['grading']['label'] ?? __('ohne Grading') }}" aria-valuenow="{{ $row['count'] }}" aria-valuemin="0" aria-valuemax="{{ $maxGrading }}">
                                    <div class="progress-bar bg-{{ $row['grading']['color'] ?? 'secondary' }}" style="width: {{ round($row['count'] / $maxGrading * 100) }}%"></div>
                                </div>
                                <span class="text-end" style="width: 2.5rem;">{{ $row['count'] }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">{{ __('Noch keine Platten erfasst.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-header">{{ __('Pro Jahr') }}</div>
                    @if ($years)
                        <div class="table-responsive">
                            <table class="table table-sm small mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Jahr') }}</th>
                                        <th class="text-end">{{ __('Erfasst') }}</th>
                                        <th class="text-end">{{ __('Verkauft') }}</th>
                                        <th class="text-end">{{ __('Erlös') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($years as $year)
                                        <tr>
                                            <td>{{ $year['year'] }}</td>
                                            <td class="text-end">{{ $year['added'] ?: '' }}</td>
                                            <td class="text-end">{{ $year['sold'] ?: '' }}</td>
                                            <td class="text-end text-nowrap">{{ $year['revenue'] ? \App\Support\Format::euro($year['revenue']) : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="card-body text-body-secondary">{{ __('Noch keine Platten erfasst.') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (count($value_by_month) >= 2)
        <script type="module">
            const style = getComputedStyle(document.documentElement);
            const muted = style.getPropertyValue('--bs-secondary-color').trim();
            const grid = style.getPropertyValue('--bs-border-color-translucent').trim();
            const euro = new Intl.NumberFormat(@js(str_replace('_', '-', app()->getLocale())), { style: 'currency', currency: 'EUR' });
            const euroShort = new Intl.NumberFormat(@js(str_replace('_', '-', app()->getLocale())), { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

            new Chart(document.getElementById('valueChart'), {
                type: 'line',
                data: {
                    labels: @json(collect($value_by_month)->map(fn ($row) => \Illuminate\Support\Carbon::createFromFormat('!Y-m', $row['month'])->locale(app()->getLocale())->isoFormat('MMM YYYY'))),
                    datasets: [{
                        label: @js(__('Wert im Bestand')),
                        data: @json(array_column($value_by_month, 'value')),
                        borderColor: '#3987e5',
                        backgroundColor: 'rgba(57, 135, 229, .12)',
                        borderWidth: 2,
                        fill: true,
                        tension: .25,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHitRadius: 12,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (context) => euro.format(context.parsed.y) } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: muted, maxRotation: 0, autoSkipPadding: 16 } },
                        y: { beginAtZero: true, border: { display: false }, grid: { color: grid }, ticks: { color: muted, callback: (value) => euroShort.format(value) } },
                    },
                },
            });
        </script>
    @endif
</x-app-layout>

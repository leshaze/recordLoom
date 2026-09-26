<x-app-layout title="Übersicht">
    <div class="container">
        <h1 class="h3 mb-4">Willkommen bei RecordLoom</h1>

        @php
            $tiles = [
                ['Platten im Bestand', $count, 'bi-vinyl', route('records.index', ['status' => 'available'])],
                ['Wert im Bestand', number_format((float) $total_value, 2, ',', '.').' €', 'bi-cash-coin', null],
                ['LPs', $count_lp, 'bi-disc', route('records.index', ['kind' => 'LP', 'status' => 'available'])],
                ['CDs', $count_cd, 'bi-disc-fill', route('records.index', ['kind' => 'CD', 'status' => 'available'])],
                ['Zum Verkauf vorgemerkt', $count_selling, 'bi-tag', route('records.index', ['status' => 'selling'])],
                ['Verkauft', $count_sold, 'bi-bag-check', route('records.index', ['status' => 'sold'])],
                ['Künstler', $count_artist, 'bi-person', route('artists.index')],
                ['Labels', $count_label, 'bi-building', route('labels.index')],
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
                                <a href="{{ $url }}" class="stretched-link" aria-label="{{ $label }} anzeigen"></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Zuletzt hinzugefügt</span>
                <a href="{{ route('records.index', ['sort' => 'created', 'dir' => 'desc']) }}" class="small">Alle anzeigen</a>
            </div>
            @if ($latest->isNotEmpty())
                <div class="list-group list-group-flush">
                    @foreach ($latest as $record)
                        <a href="{{ route('records.show', $record) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                            <x-cover :record="$record" :size="40" />
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $record->title }}</div>
                                <div class="small text-body-secondary">{{ $record->artist->name }} · {{ $record->kind }}@if ($record->release_year) · {{ $record->release_year }}@endif</div>
                            </div>
                            <span class="small text-body-secondary">{{ $record->created_at?->format('d.m.Y') }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="card-body">Noch keine Platten erfasst. <a href="{{ route('records.create') }}">Erste Platte anlegen</a></div>
            @endif
        </div>
    </div>
</x-app-layout>

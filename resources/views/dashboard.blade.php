<x-app-layout title="{{ __('Übersicht') }}">
    <div class="container">
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('Zuletzt hinzugefügt') }}</span>
                <a href="{{ route('records.index', ['sort' => 'created', 'dir' => 'desc']) }}" class="small">{{ __('Alle anzeigen') }}</a>
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
                            <span class="small text-body-secondary">{{ \App\Support\Format::date($record->created_at) }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="card-body">{{ __('Noch keine Platten erfasst.') }} <a href="{{ route('records.create') }}">{{ __('Erste Platte anlegen') }}</a></div>
            @endif
        </div>
    </div>
</x-app-layout>

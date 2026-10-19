<x-app-layout title="{{ __('Übersicht') }}">
    <div class="container-fluid px-lg-4">
        <h1 class="h3 mb-4">{{ __('Hallo :name', ['name' => auth()->user()->name]) }}</h1>

        <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
            @foreach ([
                [__('Zugewiesen, noch offen'), $open->count(), 'bi-vinyl', route('records.index', ['status' => 'available'])],
                [__('Mit deinem Interesse'), $interested, 'bi-star-fill', null],
                [__('An dich verkauft'), $bought->count(), 'bi-bag-check', route('records.index', ['status' => 'sold'])],
            ] as [$label, $value, $icon, $url])
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

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>{{ __('Dir zugewiesene Platten, noch nicht verkauft') }}</span>
                        <a href="{{ route('records.index') }}" class="small">{{ __('Alle anzeigen') }}</a>
                    </div>
                    @if ($open->isEmpty())
                        <div class="card-body text-body-secondary">{{ __('Zurzeit sind dir keine offenen Platten zugewiesen.') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($open->take(10) as $record)
                                <a href="{{ route('records.show', $record) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                    <x-cover :record="$record" :size="40" class="flex-shrink-0" />
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="fw-semibold text-truncate">{{ $record->title }}</div>
                                        <div class="small text-body-secondary text-truncate">{{ $record->artist->name }} · {{ $record->kind }}</div>
                                    </div>
                                    @if ($record->pivot->interested_at)
                                        <span class="badge text-bg-warning"><i class="bi bi-star-fill"></i> {{ __('Interesse angemeldet') }}</span>
                                    @endif
                                    @if (filled($record->current_price))
                                        <span class="text-nowrap">{{ \App\Support\Format::euro($record->current_price) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>{{ __('Benachrichtigungen') }}</span>
                        <a href="{{ route('notifications.index') }}" class="small">{{ __('Alle anzeigen') }}</a>
                    </div>
                    @if ($notifications->isEmpty())
                        <div class="card-body text-body-secondary">{{ __('Keine Benachrichtigungen.') }}</div>
                    @else
                        <div class="list-group list-group-flush small">
                            @foreach ($notifications as $notification)
                                <div @class(['list-group-item', 'list-group-item-warning' => ! $notification->read_at])>
                                    @include('notifications._text')
                                    <div class="text-body-secondary">{{ \App\Support\Format::date($notification->created_at) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

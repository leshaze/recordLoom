<x-app-layout title="{{ __('Platten') }}">
    @php $v = $filter->values; @endphp
    <div class="container-fluid px-lg-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h3 mb-0">
                {{ __($v['status'] ? \App\Support\RecordFilter::STATUS[$v['status']] : 'Alle Platten') }}
                <small class="text-body-secondary fs-6">{{ trans_choice(':count Eintrag|:count Einträge', $records->total()) }}</small>
            </h1>
            <div class="d-flex flex-wrap gap-2">
                @if ($v['status'] === 'selling')
                    <a class="btn btn-sm btn-outline-info" href="{{ route('records.print') }}" target="_blank"><i class="bi bi-filetype-pdf"></i> {{ __('Verkaufsliste (PDF)') }}</a>
                @endif
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('records.export', request()->query()) }}"><i class="bi bi-download"></i> {{ __('CSV-Export') }}</a>
                <a class="btn btn-sm btn-primary" href="{{ route('records.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Neue Platte') }}</a>
            </div>
        </div>

        @include('records._list')
    </div>
</x-app-layout>

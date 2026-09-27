<x-app-layout title="{{ __('Künstler') }}">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h3 mb-0">{{ __('Künstler') }} <small class="text-body-secondary fs-6">{{ $artists->total() }}</small></h1>
            <a class="btn btn-sm btn-primary" href="{{ route('artists.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Neuer Künstler') }}</a>
        </div>
        @if ($artists->isEmpty())
            <div class="card card-body">{{ __('Noch keine Einträge vorhanden.') }}</div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th class="text-end">{{ __('LPs') }}</th>
                                <th class="text-end">{{ __('CDs') }}</th>
                                <th class="text-end">{{ __('Wert LPs') }}</th>
                                <th class="text-end">{{ __('Wert CDs') }}</th>
                                <th>{{ __('Beschreibung') }}</th>
                                <th class="text-end">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($artists as $artist)
                                @php
                                    $own = $records->where('artist_id', $artist->id);
                                    $lps = $own->where('kind', 'LP');
                                    $cds = $own->where('kind', 'CD');
                                @endphp
                                <tr>
                                    <td><a href="{{ route('artists.show', $artist) }}" class="fw-semibold">{{ $artist->name }}</a></td>
                                    <td class="text-end">{{ $lps->count() ?: '' }}</td>
                                    <td class="text-end">{{ $cds->count() ?: '' }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Format::euro($lps->sum('current_price') ?: null) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Format::euro($cds->sum('current_price') ?: null) }}</td>
                                    <td>{{ $artist->description }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('artists.edit', $artist) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Bearbeiten') }}" aria-label="{{ __('Bearbeiten') }}"><i class="bi bi-pencil-square"></i></a>
                                        <x-delete-button :action="route('artists.destroy', $artist)" :message="__('Künstler „:name“ wirklich löschen?', ['name' => $artist->name])" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $artists->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>

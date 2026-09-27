{{-- List of artists or labels with search, sorting and paging; table on large screens, cards on phones. --}}
@props(['items', 'filter', 'resource', 'title', 'createLabel', 'deleteMessage'])

@php $v = $filter->values; @endphp
<div class="container-fluid px-lg-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h3 mb-0">
            {{ $title }}
            <small class="text-body-secondary fs-6">{{ trans_choice(':count Eintrag|:count Einträge', $items->total()) }}</small>
        </h1>
        <a class="btn btn-sm btn-primary" href="{{ route($resource.'.create') }}"><i class="bi bi-plus-lg"></i> {{ $createLabel }}</a>
    </div>

    <form method="GET" action="{{ route($filter->route) }}" class="card card-body mb-3">
        <input type="hidden" name="sort" value="{{ $v['sort'] }}">
        <input type="hidden" name="dir" value="{{ $v['dir'] }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="filter-q" class="form-label small mb-1">{{ __('Suche') }}</label>
                <input type="search" name="q" id="filter-q" value="{{ $v['q'] }}" class="form-control form-control-sm"
                    placeholder="{{ __('Name, Beschreibung …') }}">
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="filter-records" class="form-label small mb-1">{{ __('Platten') }}</label>
                <select name="records" id="filter-records" class="form-select form-select-sm" data-auto-submit>
                    <option value="">{{ __('Alle') }}</option>
                    @foreach (\App\Support\GroupFilter::RECORDS as $key => $label)
                        <option value="{{ $key }}" @selected($v['records'] === $key)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="filter-per-page" class="form-label small mb-1">{{ __('Pro Seite') }}</label>
                <select name="per_page" id="filter-per-page" class="form-select form-select-sm" data-auto-submit>
                    @foreach (\App\Support\RecordFilter::PER_PAGE as $perPage)
                        <option value="{{ $perPage }}" @selected($v['per_page'] === $perPage)>{{ $perPage }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1" title="{{ __('Filtern') }}"><i class="bi bi-search"></i></button>
                @if ($filter->isActive())
                    <a href="{{ route($filter->route) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Filter zurücksetzen') }}"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    @if ($items->isEmpty())
        <div class="card card-body">
            @if ($filter->isActive())
                {{ __('Keine Einträge gefunden.') }} <a href="{{ route($filter->route) }}">{{ __('Filter zurücksetzen') }}</a>
            @else
                {{ __('Noch keine Einträge vorhanden.') }}
            @endif
        </div>
    @else
        {{-- Table for larger screens --}}
        <div class="card d-none d-lg-block">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle small mb-0">
                    <thead>
                        <tr>
                            <th><x-sort-link :filter="$filter" sort="name">{{ __('Name') }}</x-sort-link></th>
                            <th class="text-end"><x-sort-link :filter="$filter" sort="lps">{{ __('LPs') }}</x-sort-link></th>
                            <th class="text-end"><x-sort-link :filter="$filter" sort="cds">{{ __('CDs') }}</x-sort-link></th>
                            <th class="text-end"><x-sort-link :filter="$filter" sort="lp_value">{{ __('Wert LPs') }}</x-sort-link></th>
                            <th class="text-end"><x-sort-link :filter="$filter" sort="cd_value">{{ __('Wert CDs') }}</x-sort-link></th>
                            <th>{{ __('Beschreibung') }}</th>
                            <th class="text-end">{{ __('Aktionen') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><a href="{{ route($resource.'.show', $item) }}" class="fw-semibold">{{ $item->name }}</a></td>
                                <td class="text-end">{{ $item->lps ?: '' }}</td>
                                <td class="text-end">{{ $item->cds ?: '' }}</td>
                                <td class="text-end text-nowrap">{{ \App\Support\Format::euro($item->lp_value ?: null) }}</td>
                                <td class="text-end text-nowrap">{{ \App\Support\Format::euro($item->cd_value ?: null) }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route($resource.'.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Bearbeiten') }}" aria-label="{{ __('Bearbeiten') }}"><i class="bi bi-pencil-square"></i></a>
                                    <x-delete-button :action="route($resource.'.destroy', $item)" :message="$deleteMessage($item)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards for phones and tablets --}}
        <div class="d-lg-none">
            <div class="d-flex flex-wrap column-gap-2 row-gap-1 mb-2 small">
                <span class="text-body-secondary">{{ __('Sortieren:') }}</span>
                @foreach (\App\Support\GroupFilter::SORTS as $sort => $label)
                    <x-sort-link :filter="$filter" :sort="$sort">{{ __($label) }}</x-sort-link>
                @endforeach
            </div>
            <div class="list-group">
                @foreach ($items as $item)
                    @php $value = (float) $item->lp_value + (float) $item->cd_value; @endphp
                    <a href="{{ route($resource.'.show', $item) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold text-break">{{ $item->name }}</div>
                            @if (filled($item->description))
                                <div class="small text-body-secondary text-break">{{ $item->description }}</div>
                            @endif
                            <div class="small">
                                @if ($item->lps) <span class="badge text-bg-secondary fw-normal">{{ trans_choice(':count LP|:count LPs', $item->lps) }}</span> @endif
                                @if ($item->cds) <span class="badge text-bg-secondary fw-normal">{{ trans_choice(':count CD|:count CDs', $item->cds) }}</span> @endif
                                @if (! $item->lps && ! $item->cds) <span class="text-body-secondary">{{ __('Keine Platten vorhanden.') }}</span> @endif
                            </div>
                        </div>
                        @if ($value > 0)
                            <span class="text-nowrap">{{ \App\Support\Format::euro($value) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

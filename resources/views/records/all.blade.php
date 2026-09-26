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

        <form method="GET" action="{{ route('records.index') }}" class="card card-body mb-3">
            <input type="hidden" name="sort" value="{{ $v['sort'] }}">
            <input type="hidden" name="dir" value="{{ $v['dir'] }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label for="filter-q" class="form-label small mb-1">{{ __('Suche') }}</label>
                    <input type="search" name="q" id="filter-q" value="{{ $v['q'] }}" class="form-control form-control-sm"
                        placeholder="{{ __('Titel, Künstler, Label, Nummern …') }}">
                </div>
                <div class="col-6 col-md-4 col-lg-1">
                    <label for="filter-kind" class="form-label small mb-1">{{ __('Art') }}</label>
                    <select name="kind" id="filter-kind" class="form-select form-select-sm" data-auto-submit>
                        <option value="">{{ __('Alle') }}</option>
                        @foreach (['LP', 'CD'] as $kind)
                            <option value="{{ $kind }}" @selected($v['kind'] === $kind)>{{ $kind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="filter-status" class="form-label small mb-1">{{ __('Status') }}</label>
                    <select name="status" id="filter-status" class="form-select form-select-sm" data-auto-submit>
                        <option value="">{{ __('Alle') }}</option>
                        @foreach (\App\Support\RecordFilter::STATUS as $key => $label)
                            <option value="{{ $key }}" @selected($v['status'] === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="filter-label" class="form-label small mb-1">{{ __('Label') }}</label>
                    <select name="label" id="filter-label" class="form-select form-select-sm" data-auto-submit>
                        <option value="">{{ __('Alle') }}</option>
                        @foreach ($labels as $label)
                            <option value="{{ $label->id }}" @selected($v['label'] === $label->id)>{{ $label->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-1">
                    <label for="filter-country" class="form-label small mb-1">{{ __('Land') }}</label>
                    <select name="country" id="filter-country" class="form-select form-select-sm" data-auto-submit>
                        <option value="">{{ __('Alle') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected($v['country'] === $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-1">
                    <label for="filter-edition" class="form-label small mb-1">{{ __('Zusatzinfo') }}</label>
                    <select name="edition" id="filter-edition" class="form-select form-select-sm" data-auto-submit>
                        <option value="">{{ __('Alle') }}</option>
                        @foreach ($editions as $edition)
                            <option value="{{ $edition->id }}" @selected($v['edition'] === $edition->id)>{{ $edition->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-1">
                    <label for="filter-per-page" class="form-label small mb-1">{{ __('Pro Seite') }}</label>
                    <select name="per_page" id="filter-per-page" class="form-select form-select-sm" data-auto-submit>
                        @foreach (\App\Support\RecordFilter::PER_PAGE as $perPage)
                            <option value="{{ $perPage }}" @selected($v['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1" title="{{ __('Filtern') }}"><i class="bi bi-search"></i></button>
                    @if ($filter->isActive())
                        <a href="{{ route('records.index') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Filter zurücksetzen') }}"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>

        @if ($records->isEmpty())
            <div class="card card-body">
                @if ($filter->isActive())
                    {{ __('Keine Platten gefunden.') }} <a href="{{ route('records.index') }}">{{ __('Filter zurücksetzen') }}</a>
                @else
                    {{ __('Noch keine Platten erfasst.') }} <a href="{{ route('records.create') }}">{{ __('Erste Platte anlegen') }}</a>
                @endif
            </div>
        @else
            {{-- Table for larger screens --}}
            <div class="card d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('Art') }}</th>
                                <th><x-sort-link :filter="$filter" sort="artist">{{ __('Künstler') }}</x-sort-link></th>
                                <th><x-sort-link :filter="$filter" sort="title">{{ __('Titel') }}</x-sort-link></th>
                                <th><x-sort-link :filter="$filter" sort="label">{{ __('Label') }}</x-sort-link></th>
                                <th><x-sort-link :filter="$filter" sort="grading">{{ __('Grading') }}</x-sort-link></th>
                                <th>{{ __('Katalog-Nr.') }}</th>
                                <th>{{ __('Archiv-Nr.') }}</th>
                                <th><x-sort-link :filter="$filter" sort="year">{{ __('Jahr') }}</x-sort-link></th>
                                <th>{{ __('Land') }}</th>
                                <th class="text-end"><x-sort-link :filter="$filter" sort="price">{{ __('Preis') }}</x-sort-link></th>
                                <th class="text-end">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                <tr @class(['text-decoration-line-through opacity-50' => $record->sold || $record->lost])>
                                    <td><x-cover :record="$record" :size="36" /></td>
                                    <td>{{ $record->kind }}</td>
                                    <td><a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a></td>
                                    <td>
                                        <a href="{{ route('records.show', $record) }}" class="fw-semibold">{{ $record->title }}</a>
                                        @foreach ($record->editions as $edition)
                                            <span class="badge text-bg-dark border fw-normal">{{ $edition->name }}</span>
                                        @endforeach
                                        @if ($record->selling && ! $record->sold) <span class="badge text-bg-info fw-normal">{{ __('Verkauf') }}</span> @endif
                                        @if ($record->sold) <span class="badge text-bg-secondary fw-normal">{{ __('Verkauft') }}</span> @endif
                                        @if ($record->lost) <span class="badge text-bg-danger fw-normal">{{ __('Verloren') }}</span> @endif
                                    </td>
                                    <td><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></td>
                                    <td class="text-nowrap">
                                        @if ($record->gradingMedia() || $record->gradingCover())
                                            <span class="text-body-secondary">{{ __('M') }}</span> <x-grading-badge :grading="$record->gradingMedia()" />
                                            <span class="text-body-secondary ms-1">{{ __('C') }}</span> <x-grading-badge :grading="$record->gradingCover()" />
                                        @endif
                                    </td>
                                    <td>{{ $record->catalog_number }}</td>
                                    <td>{{ $record->archive_number }}</td>
                                    <td>{{ $record->release_year }}</td>
                                    <td>{{ $record->country?->name }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Format::euro($record->current_price) }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('records.edit', $record) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Bearbeiten') }}" aria-label="{{ __('Bearbeiten') }}"><i class="bi bi-pencil-square"></i></a>
                                        <x-delete-button :action="route('records.destroy', $record)"
                                            :message="__('Die Platte „:title“ von :artist wirklich löschen?', ['title' => $record->title, 'artist' => $record->artist->name])" />
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
                    @foreach (\App\Support\RecordFilter::SORTS as $sort => $label)
                        <x-sort-link :filter="$filter" :sort="$sort">{{ __($label) }}</x-sort-link>
                    @endforeach
                </div>
                <div class="list-group">
                    @foreach ($records as $record)
                        <a href="{{ route('records.show', $record) }}" @class(['list-group-item list-group-item-action d-flex gap-3 align-items-center', 'opacity-50' => $record->sold || $record->lost])>
                            <x-cover :record="$record" :size="56" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $record->title }}</div>
                                <div class="small text-body-secondary text-truncate">{{ $record->artist->name }} · {{ $record->kind }}@if ($record->release_year) · {{ $record->release_year }}@endif</div>
                                <div class="small">
                                    <x-grading-badge :grading="$record->gradingMedia()" />
                                    @if ($record->sold) <span class="badge text-bg-secondary">{{ __('Verkauft') }}</span>
                                    @elseif ($record->selling) <span class="badge text-bg-info">{{ __('Verkauf') }}</span> @endif
                                    @foreach ($record->editions as $edition)
                                        <span class="badge text-bg-dark border">{{ $edition->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @if (filled($record->current_price))
                                <span class="text-nowrap">{{ \App\Support\Format::euro($record->current_price) }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                {{ $records->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</x-app-layout>

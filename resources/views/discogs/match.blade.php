<x-app-layout title="{{ __('Mit Discogs abgleichen') }}">
    <div class="container" style="max-width: 70rem;">
        <h1 class="h3 mb-1">{{ __('Mit Discogs abgleichen') }}</h1>
        <p class="text-body-secondary">
            {{ __('Verknüpfe vorhandene Platten mit der passenden Discogs-Pressung. Vorgeschlagen wird per Barcode, Katalog-Nr. oder Künstler und Titel. Es werden nur leere Felder und ein fehlendes Cover ergänzt, nichts wird überschrieben.') }}
        </p>
        <p class="small">
            {{ trans_choice(':count Platte ist verknüpft.|:count Platten sind verknüpft.', $linkedCount) }}
            {{ trans_choice(':count Platte ohne Verknüpfung.|:count Platten ohne Verknüpfung.', $records->total()) }}
            @if (request()->boolean('ignored'))
                <a href="{{ route('discogs.match') }}">{{ __('Übersprungene ausblenden') }}</a>
            @elseif ($ignoredCount)
                <a href="{{ route('discogs.match', ['ignored' => 1]) }}">{{ trans_choice(':count übersprungene Platte anzeigen|:count übersprungene Platten anzeigen', $ignoredCount) }}</a>
            @endif
        </p>

        @unless ($configured)
            <div class="alert alert-warning">{{ __('Discogs ist nicht eingerichtet. Bitte DISCOGS_TOKEN in der .env eintragen.') }}</div>
        @endunless

        @forelse ($records as $record)
            <div class="card mb-3" data-discogs-match data-suggestions-url="{{ route('discogs.suggestions', $record) }}"
                data-link-url="{{ route('discogs.link', $record) }}"
                data-texts="{{ json_encode([
                    'loading' => __('Suche Vorschläge …'),
                    'none' => __('Keine Vorschläge gefunden. Du kannst die Release-ID auch von Hand eintragen.'),
                    'link' => __('Verknüpfen'),
                    'show' => __('Auf Discogs ansehen'),
                    'error' => __('Discogs ist gerade nicht erreichbar.'),
                ]) }}">
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-start">
                        <x-cover :record="$record" :size="56" class="flex-shrink-0" />
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold"><a href="{{ route('records.show', $record) }}">{{ $record->title }}</a>
                                @if ($record->discogs_ignored_at) <span class="badge text-bg-secondary">{{ __('Übersprungen') }}</span> @endif
                            </div>
                            <div class="small text-body-secondary">
                                {{ $record->artist->name }} · {{ $record->label->name }} · {{ $record->kind }}
                                @if ($record->catalog_number) · {{ __('Katalog-Nr.') }} {{ $record->catalog_number }} @endif
                                @if ($record->barcode) · {{ __('Barcode') }} {{ $record->barcode }} @endif
                                @if ($record->release_year) · {{ $record->release_year }} @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-outline-info" data-load-suggestions @disabled(! $configured)>
                            <i class="bi bi-search"></i> {{ __('Vorschläge laden') }}
                        </button>
                        <form method="POST" action="{{ route('discogs.link', $record) }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="fill_missing" value="1">
                            <input type="number" name="release_id" min="1" required class="form-control form-control-sm" style="width: 10rem;"
                                placeholder="{{ __('Release-ID') }}" aria-label="{{ __('Discogs-Release-ID') }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary" @disabled(! $configured)>{{ __('Verknüpfen') }}</button>
                        </form>
                        @unless ($record->discogs_ignored_at)
                            <form method="POST" action="{{ route('discogs.ignore', $record) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Keine passende Pressung') }}</button>
                            </form>
                        @endunless
                    </div>
                    <div class="small mt-2" data-status role="status"></div>
                    <div class="list-group mt-2" data-results></div>
                </div>
            </div>
        @empty
            <div class="card card-body">{{ __('Alle Platten sind mit Discogs verknüpft oder übersprungen.') }}</div>
        @endforelse

        <div class="mt-3">{{ $records->links('pagination::bootstrap-5') }}</div>
        <div class="small text-body-secondary">{{ __('Daten von') }} <a href="https://www.discogs.com" target="_blank" rel="noopener noreferrer">Discogs</a></div>
    </div>
</x-app-layout>

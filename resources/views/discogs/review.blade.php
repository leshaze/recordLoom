<x-app-layout title="{{ __('Angaben von Discogs übernehmen') }}">
    <div class="container" style="max-width: 60rem;">
        <h1 class="h3 mb-1">{{ __('Angaben von Discogs übernehmen') }}</h1>
        <p class="text-body-secondary">
            {{ __('Wähle je Feld, welcher Wert gelten soll. Vorbelegt ist immer der vorhandene Wert, leere Felder werden mit Discogs gefüllt.') }}
            <a href="https://www.discogs.com/release/{{ $releaseId }}" target="_blank" rel="noopener noreferrer">{{ __('Auf Discogs ansehen') }} <i class="bi bi-box-arrow-up-right"></i></a>
        </p>

        <form method="POST" action="{{ route('discogs.link', $record) }}" id="discogs-review">
            @csrf
            <input type="hidden" name="release_id" value="{{ $releaseId }}">

            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-choose-all="keep">{{ __('Überall vorhandene Werte') }}</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-choose-all="discogs">{{ __('Überall Discogs') }}</button>
            </div>

            <div class="card mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 20%;">{{ __('Feld') }}</th>
                                <th style="width: 40%;">{{ __('Vorhanden') }}</th>
                                <th style="width: 40%;">Discogs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <th class="fw-semibold">{{ __($row['label']) }}</th>
                                    @if ($row['same'])
                                        <td colspan="2" class="text-body-secondary">
                                            {{ $row['current'] }} <span class="small">({{ __('gleich') }})</span>
                                            <input type="hidden" name="use[{{ $row['field'] }}]" value="keep">
                                        </td>
                                    @else
                                        @foreach (['keep' => $row['current'], 'discogs' => $row['discogs']] as $choice => $value)
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="use[{{ $row['field'] }}]" value="{{ $choice }}"
                                                        id="use-{{ $row['field'] }}-{{ $choice }}" @checked($row['default'] === $choice)>
                                                    <label class="form-check-label text-break" for="use-{{ $row['field'] }}-{{ $choice }}">
                                                        @if ($value === null) <span class="text-body-secondary">{{ __('leer') }}</span> @else {{ $value }} @endif
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach

                            @if (filled($release['cover_url'] ?? null))
                                <tr>
                                    <th class="fw-semibold">{{ __('Cover') }}</th>
                                    <td>
                                        <div class="form-check d-flex gap-2 align-items-center">
                                            <input class="form-check-input" type="radio" name="cover" value="keep" id="cover-keep" @checked($record->hasCover())>
                                            <label class="form-check-label" for="cover-keep">
                                                @if ($record->hasCover()) <x-cover :record="$record" :size="64" /> @else <span class="text-body-secondary">{{ __('kein Cover') }}</span> @endif
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check d-flex gap-2 align-items-center">
                                            <input class="form-check-input" type="radio" name="cover" value="discogs" id="cover-discogs" @checked(! $record->hasCover())>
                                            <label class="form-check-label" for="cover-discogs">
                                                <img src="{{ $release['cover_url'] }}" alt="" width="64" height="64" class="rounded object-fit-cover" referrerpolicy="no-referrer" loading="lazy">
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if ($newEditions->isNotEmpty())
                                <tr>
                                    <th class="fw-semibold">{{ __('Zusatzinfos') }}</th>
                                    <td class="text-body-secondary small">{{ $record->editions->pluck('label')->implode(', ') ?: __('leer') }}</td>
                                    <td>
                                        @foreach ($newEditions as $edition)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="editions[]" value="{{ $edition->id }}" id="edition-{{ $edition->id }}" checked>
                                                <label class="form-check-label" for="edition-{{ $edition->id }}">+ {{ $edition->label }}</label>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-link-45deg"></i> {{ __('Verknüpfen und übernehmen') }}</button>
                <a href="{{ route('discogs.match') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
        <div class="small text-body-secondary">{{ __('Daten von') }} <a href="https://www.discogs.com" target="_blank" rel="noopener noreferrer">Discogs</a></div>
    </div>
</x-app-layout>

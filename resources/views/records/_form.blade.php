{{-- Form fields shared by records.create and records.edit. Expects $record (new or existing) and $editions. --}}
@php
    $value = fn (string $field) => old($field, $record->{$field});
    $isNew = ! $record->exists;
    $selectedEditions = collect(old('editions', $record->exists ? $record->editions->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $sold = (bool) old('sold', $record->sold);
@endphp

<p class="small text-body-secondary mb-3">{!! __('Felder mit :star sind Pflichtfelder.', ['star' => '<span class="text-danger">*</span>']) !!}</p>

<div class="card mb-3 border-info-subtle" id="discogs-card"
    data-search-url="{{ route('discogs.search') }}" data-release-url="{{ url('discogs/releases') }}"
    data-texts="{{ json_encode([
        'searching' => __('Suche bei Discogs …'),
        'loading' => __('Lade Angaben von Discogs …'),
        'none' => __('Keine Treffer. Versuche es mit weniger oder anderen Suchbegriffen.'),
        'apply' => __('Übernehmen'),
        'applied' => __('Angaben von Discogs übernommen. Bitte prüfen und Grading und Preis ergänzen.'),
        'error' => __('Discogs ist gerade nicht erreichbar.'),
        'show' => __('Auf Discogs ansehen'),
        'empty' => __('leer'),
        'cover' => __('Cover'),
        'editions' => __('Zusatzinfos'),
        'fields' => collect(\App\Services\Discogs\DiscogsComparison::FIELDS)->map(fn ($label) => __($label)),
    ]) }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-vinyl"></i> Discogs</span>
        @if ($record->discogsUrl())
            <a href="{{ $record->discogsUrl() }}" target="_blank" rel="noopener noreferrer" class="small">{{ __('Auf Discogs ansehen') }} <i class="bi bi-box-arrow-up-right"></i></a>
        @endif
    </div>
    <div class="card-body">
        @if ($discogsConfigured)
            <label for="discogs-query" class="form-label">{{ __('Bei Discogs suchen und Angaben übernehmen') }}</label>
            <div class="input-group">
                <input type="search" id="discogs-query" class="form-control" autocomplete="off"
                    placeholder="{{ __('Barcode, Katalog-Nr. oder Künstler und Titel') }}">
                <button type="button" class="btn btn-outline-info" id="discogs-search"><i class="bi bi-search"></i> {{ __('Suchen') }}</button>
            </div>
            <div class="form-text">{{ __('Nach der Auswahl werden Titel, Künstler, Label, Nummern, Land, Jahr, Zusatzinfos und Cover ausgefüllt. Grading und Preise bleiben unverändert.') }}</div>
            <div id="discogs-status" class="small mt-2" role="status"></div>
            <div id="discogs-results" class="list-group mt-2"></div>
        @else
            <div class="small text-body-secondary">{{ __('Discogs ist nicht eingerichtet. Bitte DISCOGS_TOKEN in der .env eintragen.') }}</div>
        @endif

        <div class="row g-2 mt-2 align-items-end">
            <div class="col-sm-5">
                <label for="discogs_release_id" class="form-label small mb-1">{{ __('Discogs-Release-ID') }}</label>
                <input type="number" min="1" name="discogs_release_id" id="discogs_release_id"
                    class="form-control form-control-sm @error('discogs_release_id') is-invalid @enderror"
                    value="{{ old('discogs_release_id', $record->discogs_release_id) }}">
                @error('discogs_release_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-7 small text-body-secondary">{{ __('Wird bei der Übernahme gesetzt. Leer lassen, um die Verknüpfung zu entfernen.') }}</div>
        </div>
        <input type="hidden" name="discogs_cover_url" id="discogs_cover_url" value="{{ old('discogs_cover_url') }}">
        <div class="small text-body-secondary mt-2">{{ __('Daten von') }} <a href="https://www.discogs.com" target="_blank" rel="noopener noreferrer">Discogs</a></div>
    </div>
</div>

<div class="modal fade" id="discogs-compare" tabindex="-1" aria-labelledby="discogs-compare-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="discogs-compare-title">{{ __('Welche Angaben sollen gelten?') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Schließen') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">{{ __('Diese Felder sind schon ausgefüllt und weichen von Discogs ab. Vorbelegt ist der vorhandene Wert, leere Felder werden automatisch gefüllt.') }}</p>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-compare-all="keep">{{ __('Überall vorhandene Werte') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-compare-all="discogs">{{ __('Überall Discogs') }}</button>
                </div>
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 22%;">{{ __('Feld') }}</th>
                            <th style="width: 39%;">{{ __('Vorhanden') }}</th>
                            <th style="width: 39%;">Discogs</th>
                        </tr>
                    </thead>
                    <tbody id="discogs-compare-rows"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Abbrechen') }}</button>
                <button type="button" class="btn btn-primary" id="discogs-compare-apply">{{ __('Übernehmen') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Stammdaten') }}</div>
    <div class="card-body"><div class="row g-3 mb-0">
        <div class="col-12">
            <span class="form-label d-block">{{ __('Art') }} <span class="text-danger">*</span></span>
            @foreach (['LP', 'CD'] as $kind)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="kind" id="kind-{{ $kind }}" value="{{ $kind }}"
                        @checked(old('kind', $record->kind ?? 'LP') === $kind) required>
                    <label class="form-check-label" for="kind-{{ $kind }}">{{ $kind }}</label>
                </div>
            @endforeach
            @error('kind') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="artist_name" class="form-label">{{ __('Künstler') }} <span class="text-danger">*</span></label>
            <input type="text" name="artist_name" id="artist_name" required maxlength="255" autocomplete="off"
                class="form-control @error('artist_name') is-invalid @enderror"
                value="{{ old('artist_name', $record->artist?->name) }}" @if ($isNew) autofocus @endif>
            <input type="hidden" name="artist_id" id="artist_id" value="{{ old('artist_id', $record->artist_id) }}">
            @error('artist_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="title" class="form-label">{{ __('Titel') }} <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title" required maxlength="255" autocomplete="off"
                class="form-control @error('title') is-invalid @enderror" value="{{ $value('title') }}">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="label_name" class="form-label">{{ __('Label') }} <span class="text-danger">*</span></label>
            <input type="text" name="label_name" id="label_name" required maxlength="255" autocomplete="off"
                class="form-control @error('label_name') is-invalid @enderror"
                value="{{ old('label_name', $record->label?->name) }}">
            <input type="hidden" name="label_id" id="label_id" value="{{ old('label_id', $record->label_id) }}">
            @error('label_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <span class="form-label d-block">{{ __('Zusatzinfos') }}
                <a href="{{ route('editions.index') }}" class="small ms-1" target="_blank">{{ __('verwalten') }}</a></span>
            @forelse ($editions as $edition)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="editions[]" id="edition-{{ $edition->id }}"
                        value="{{ $edition->id }}" @checked($selectedEditions->contains($edition->id))>
                    <label class="form-check-label" for="edition-{{ $edition->id }}">{{ $edition->label }}</label>
                </div>
            @empty
                <span class="small text-body-secondary">{{ __('Noch keine Zusatzinfos angelegt.') }}</span>
            @endforelse
            @error('editions.*') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div></div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Details') }}</div>
    <div class="card-body"><div class="row g-3 mb-0">
        @foreach (['catalog_number' => 'Katalog-Nr.', 'matrix_number' => 'Matrix-Nr.', 'barcode' => 'Barcode', 'archive_number' => 'Archiv-Nr.'] as $field => $label)
            <div class="col-sm-6 col-md-3">
                <label for="{{ $field }}" class="form-label">{{ __($label) }}</label>
                <input type="text" name="{{ $field }}" id="{{ $field }}" maxlength="255"
                    class="form-control @error($field) is-invalid @enderror" value="{{ $value($field) }}">
                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endforeach
        <div class="col-sm-6 col-md-4">
            <label for="country_name" class="form-label">{{ __('Herkunftsland') }}</label>
            <input type="text" name="country_name" id="country_name" maxlength="255" autocomplete="off"
                class="form-control @error('country_name') is-invalid @enderror"
                value="{{ old('country_name', $record->country?->name) }}">
            <input type="hidden" name="country_id" id="country_id" value="{{ old('country_id', $record->country_id) }}">
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="release_year" class="form-label">{{ __('Erscheinungsjahr') }}</label>
            <input type="number" name="release_year" id="release_year" min="1900" max="{{ now()->year + 1 }}" step="1"
                placeholder="{{ __('z. B. 1974') }}" class="form-control @error('release_year') is-invalid @enderror" value="{{ $value('release_year') }}">
            @error('release_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="reissue_year" class="form-label">{{ __('Jahr der Neuauflage') }}</label>
            <input type="number" name="reissue_year" id="reissue_year" min="1900" max="{{ now()->year + 1 }}" step="1"
                class="form-control @error('reissue_year') is-invalid @enderror" value="{{ $value('reissue_year') }}">
            @error('reissue_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div></div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Zustand & Preis') }}</div>
    <div class="card-body"><div class="row g-3 mb-0">
        @foreach (['grading_media' => 'Grading Media', 'grading_cover' => 'Grading Cover'] as $field => $label)
            <div class="col-sm-6 col-md-3">
                <label for="{{ $field }}" class="form-label">{{ __($label) }}</label>
                <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                    <option value="">{{ __('– nicht bewertet –') }}</option>
                    @foreach (\App\Support\Grading::options() as $grade => $gradeLabel)
                        <option value="{{ $grade }}" @selected((int) $value($field) === $grade)>{{ $gradeLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-sm-6 col-md-2">
            <label for="current_price" class="form-label">{{ __('Aktueller Preis') }}</label>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="current_price" id="current_price"
                    class="form-control @error('current_price') is-invalid @enderror" value="{{ $value('current_price') }}">
                <span class="input-group-text">€</span>
                @error('current_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-sm-6 col-md-2">
            <label for="buy_price" class="form-label">{{ __('Kaufpreis') }}</label>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="buy_price" id="buy_price"
                    class="form-control @error('buy_price') is-invalid @enderror" value="{{ $value('buy_price') }}">
                <span class="input-group-text">€</span>
                @error('buy_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-sm-12 col-md-2">
            <label for="platform" class="form-label">{{ __('Anbieter') }}</label>
            <input type="text" name="platform" id="platform" maxlength="255" autocomplete="off"
                class="form-control @error('platform') is-invalid @enderror"
                value="{{ old('platform', $record->platform?->name) }}">
            <input type="hidden" name="platform_id" id="platform_id" value="{{ old('platform_id', $record->platform_id) }}">
        </div>
    </div></div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Cover') }}</div>
    <div class="card-body d-flex flex-wrap gap-3 align-items-start">
        @if ($record->hasCover())
            <div>
                <x-cover :record="$record" :size="120" />
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover" value="1">
                    <label class="form-check-label" for="remove_cover">{{ __('Cover entfernen') }}</label>
                </div>
            </div>
        @endif
        <div class="flex-grow-1">
            <label for="cover" class="form-label">{{ __($record->hasCover() ? 'Neues Cover hochladen' : 'Cover hochladen') }}</label>
            <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/webp,image/gif"
                class="form-control @error('cover') is-invalid @enderror">
            <div class="form-text">{{ __('JPG, PNG, WebP oder GIF, maximal 8 MB.') }}</div>
            @error('cover') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <img id="cover-preview" class="d-none rounded mt-2" style="max-width: 160px;" alt="{{ __('Vorschau') }}">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Verkauf') }}</div>
    <div class="card-body"><div class="row g-3 mb-0">
        <div class="col-12">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="selling" name="selling" value="1" @checked(old('selling', $record->selling))>
                <label class="form-check-label" for="selling">{{ __('Zum Verkauf vormerken') }}</label>
            </div>
            @unless ($isNew)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="sold" name="sold" value="1" @checked($sold)>
                    <label class="form-check-label" for="sold">{{ __('Verkauft') }}</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="lost" name="lost" value="1" @checked(old('lost', $record->lost))>
                    <label class="form-check-label" for="lost">{{ __('Verloren') }}</label>
                </div>
            @endunless
        </div>
        @unless ($isNew)
            <div id="sold-fields" @class(['row g-3 m-0 p-0', 'd-none' => ! $sold])>
                <div class="col-sm-4">
                    <label for="sold_on" class="form-label">{{ __('Verkaufsdatum') }}</label>
                    <input type="date" name="sold_on" id="sold_on" class="form-control @error('sold_on') is-invalid @enderror"
                        value="{{ old('sold_on', $record->sold_on?->format('Y-m-d')) }}">
                    @error('sold_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-4">
                    <label for="sold_to" class="form-label">{{ __('Verkauft an') }}</label>
                    <input type="text" name="sold_to" id="sold_to" maxlength="255" class="form-control" value="{{ $value('sold_to') }}">
                </div>
                <div class="col-sm-4">
                    <label for="sold_price" class="form-label">{{ __('Verkaufspreis') }}</label>
                    <div class="input-group">
                        <input type="text" inputmode="decimal" name="sold_price" id="sold_price"
                            class="form-control @error('sold_price') is-invalid @enderror" value="{{ $value('sold_price') }}">
                        <span class="input-group-text">€</span>
                        @error('sold_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        @endunless
    </div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><label for="note" class="m-0">{{ __('Notiz') }}</label></div>
    <div class="card-body">
        <textarea class="form-control @error('note') is-invalid @enderror" name="note" id="note" rows="4" maxlength="5000">{{ $value('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

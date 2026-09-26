{{-- Form fields shared by records.create and records.edit. Expects $record (new or existing) and $editions. --}}
@php
    $value = fn (string $field) => old($field, $record->{$field});
    $isNew = ! $record->exists;
    $selectedEditions = collect(old('editions', $record->exists ? $record->editions->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $sold = (bool) old('sold', $record->sold);
@endphp

<p class="small text-body-secondary mb-3">Felder mit <span class="text-danger">*</span> sind Pflichtfelder.</p>

<div class="card mb-3">
    <div class="card-header">Stammdaten</div>
    <div class="card-body row g-3">
        <div class="col-12">
            <span class="form-label d-block">Art <span class="text-danger">*</span></span>
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
            <label for="artist_name" class="form-label">Künstler <span class="text-danger">*</span></label>
            <input type="text" name="artist_name" id="artist_name" required maxlength="255" autocomplete="off"
                class="form-control @error('artist_name') is-invalid @enderror"
                value="{{ old('artist_name', $record->artist?->name) }}" @if ($isNew) autofocus @endif>
            <input type="hidden" name="artist_id" id="artist_id" value="{{ old('artist_id', $record->artist_id) }}">
            @error('artist_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="title" class="form-label">Titel <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title" required maxlength="255" autocomplete="off"
                class="form-control @error('title') is-invalid @enderror" value="{{ $value('title') }}">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="label_name" class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label_name" id="label_name" required maxlength="255" autocomplete="off"
                class="form-control @error('label_name') is-invalid @enderror"
                value="{{ old('label_name', $record->label?->name) }}">
            <input type="hidden" name="label_id" id="label_id" value="{{ old('label_id', $record->label_id) }}">
            @error('label_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <span class="form-label d-block">Zusatzinfos
                <a href="{{ route('editions.index') }}" class="small ms-1" target="_blank">verwalten</a></span>
            @forelse ($editions as $edition)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="editions[]" id="edition-{{ $edition->id }}"
                        value="{{ $edition->id }}" @checked($selectedEditions->contains($edition->id))>
                    <label class="form-check-label" for="edition-{{ $edition->id }}">{{ $edition->name }}</label>
                </div>
            @empty
                <span class="small text-body-secondary">Noch keine Zusatzinfos angelegt.</span>
            @endforelse
            @error('editions.*') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Details</div>
    <div class="card-body row g-3">
        @foreach (['catalog_number' => 'Katalog-Nr.', 'matrix_number' => 'Matrix-Nr.', 'barcode' => 'Barcode', 'archive_number' => 'Archiv-Nr.'] as $field => $label)
            <div class="col-sm-6 col-md-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <input type="text" name="{{ $field }}" id="{{ $field }}" maxlength="255"
                    class="form-control @error($field) is-invalid @enderror" value="{{ $value($field) }}">
                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endforeach
        <div class="col-sm-6 col-md-4">
            <label for="country_name" class="form-label">Herkunftsland</label>
            <input type="text" name="country_name" id="country_name" maxlength="255" autocomplete="off"
                class="form-control @error('country_name') is-invalid @enderror"
                value="{{ old('country_name', $record->country?->name) }}">
            <input type="hidden" name="country_id" id="country_id" value="{{ old('country_id', $record->country_id) }}">
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="release_year" class="form-label">Erscheinungsjahr</label>
            <input type="number" name="release_year" id="release_year" min="1900" max="{{ now()->year + 1 }}" step="1"
                placeholder="z. B. 1974" class="form-control @error('release_year') is-invalid @enderror" value="{{ $value('release_year') }}">
            @error('release_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="reissue_year" class="form-label">Jahr der Neuauflage</label>
            <input type="number" name="reissue_year" id="reissue_year" min="1900" max="{{ now()->year + 1 }}" step="1"
                class="form-control @error('reissue_year') is-invalid @enderror" value="{{ $value('reissue_year') }}">
            @error('reissue_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Zustand &amp; Preis</div>
    <div class="card-body row g-3">
        @foreach (['grading_media' => 'Grading Media', 'grading_cover' => 'Grading Cover'] as $field => $label)
            <div class="col-sm-6 col-md-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                    <option value="">– nicht bewertet –</option>
                    @foreach (\App\Support\Grading::options() as $grade => $gradeLabel)
                        <option value="{{ $grade }}" @selected((int) $value($field) === $grade)>{{ $gradeLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-sm-6 col-md-2">
            <label for="current_price" class="form-label">Aktueller Preis</label>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="current_price" id="current_price"
                    class="form-control @error('current_price') is-invalid @enderror" value="{{ $value('current_price') }}">
                <span class="input-group-text">€</span>
                @error('current_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-sm-6 col-md-2">
            <label for="buy_price" class="form-label">Kaufpreis</label>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="buy_price" id="buy_price"
                    class="form-control @error('buy_price') is-invalid @enderror" value="{{ $value('buy_price') }}">
                <span class="input-group-text">€</span>
                @error('buy_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-sm-12 col-md-2">
            <label for="platform" class="form-label">Anbieter</label>
            <input type="text" name="platform" id="platform" maxlength="255" autocomplete="off"
                class="form-control @error('platform') is-invalid @enderror"
                value="{{ old('platform', $record->platform?->name) }}">
            <input type="hidden" name="platform_id" id="platform_id" value="{{ old('platform_id', $record->platform_id) }}">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Cover</div>
    <div class="card-body d-flex flex-wrap gap-3 align-items-start">
        @if ($record->hasCover())
            <div>
                <x-cover :record="$record" :size="120" />
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover" value="1">
                    <label class="form-check-label" for="remove_cover">Cover entfernen</label>
                </div>
            </div>
        @endif
        <div class="flex-grow-1">
            <label for="cover" class="form-label">{{ $record->hasCover() ? 'Neues Cover hochladen' : 'Cover hochladen' }}</label>
            <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/webp,image/gif"
                class="form-control @error('cover') is-invalid @enderror">
            <div class="form-text">JPG, PNG, WebP oder GIF, maximal 8 MB.</div>
            @error('cover') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <img id="cover-preview" class="d-none rounded mt-2" style="max-width: 160px;" alt="Vorschau">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Verkauf</div>
    <div class="card-body row g-3">
        <div class="col-12">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="selling" name="selling" value="1" @checked(old('selling', $record->selling))>
                <label class="form-check-label" for="selling">Zum Verkauf vormerken</label>
            </div>
            @unless ($isNew)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="sold" name="sold" value="1" @checked($sold)>
                    <label class="form-check-label" for="sold">Verkauft</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="lost" name="lost" value="1" @checked(old('lost', $record->lost))>
                    <label class="form-check-label" for="lost">Verloren</label>
                </div>
            @endunless
        </div>
        @unless ($isNew)
            <div id="sold-fields" @class(['row g-3 m-0 p-0', 'd-none' => ! $sold])>
                <div class="col-sm-4">
                    <label for="sold_on" class="form-label">Verkaufsdatum</label>
                    <input type="date" name="sold_on" id="sold_on" class="form-control @error('sold_on') is-invalid @enderror"
                        value="{{ old('sold_on', $record->sold_on?->format('Y-m-d')) }}">
                    @error('sold_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-4">
                    <label for="sold_to" class="form-label">Verkauft an</label>
                    <input type="text" name="sold_to" id="sold_to" maxlength="255" class="form-control" value="{{ $value('sold_to') }}">
                </div>
                <div class="col-sm-4">
                    <label for="sold_price" class="form-label">Verkaufspreis</label>
                    <div class="input-group">
                        <input type="text" inputmode="decimal" name="sold_price" id="sold_price"
                            class="form-control @error('sold_price') is-invalid @enderror" value="{{ $value('sold_price') }}">
                        <span class="input-group-text">€</span>
                        @error('sold_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        @endunless
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><label for="note" class="m-0">Notiz</label></div>
    <div class="card-body">
        <textarea class="form-control @error('note') is-invalid @enderror" name="note" id="note" rows="4" maxlength="5000">{{ $value('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

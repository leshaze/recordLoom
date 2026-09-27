<x-app-layout title="{{ __('Zusatzinfos') }}">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-1">{{ __('Zusatzinfos') }}</h1>
        <p class="text-body-secondary">{{ __('Zusätzliche Informationen wie „Boxset“ oder „Erstpressung“, die du bei jeder Platte auswählen kannst.') }}
            {{ __('Ohne englischen Namen wird auch auf Englisch der deutsche Name angezeigt.') }}</p>

        <form method="POST" action="{{ route('editions.store') }}" class="card card-body mb-3">
            @csrf
            <div class="fw-semibold mb-2">{{ __('Neue Zusatzinfo') }}</div>
            <div class="row g-2">
                <div class="col-sm-5">
                    <label for="name" class="form-label small mb-1">{{ __('Name (deutsch)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" maxlength="100" required placeholder="{{ __('z. B. Promo') }}"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-5">
                    <label for="name_en" class="form-label small mb-1">{{ __('Name (englisch)') }}</label>
                    <input type="text" name="name_en" id="name_en" maxlength="100" placeholder="{{ __('z. B. Promo') }}"
                        class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}">
                    @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> {{ __('Hinzufügen') }}</button>
                </div>
            </div>
        </form>

        <div class="card">
            @forelse ($editions as $edition)
                @php $bag = 'edition'.$edition->id; @endphp
                <div class="card-body border-bottom py-2">
                    <form method="POST" action="{{ route('editions.update', $edition) }}" class="row g-2 align-items-center">
                        @csrf
                        @method('PUT')
                        <div class="col-sm-4">
                            <input type="text" name="name" maxlength="100" required aria-label="{{ __('Name (deutsch)') }}"
                                placeholder="{{ __('Name (deutsch)') }}"
                                class="form-control form-control-sm @error('name', $bag) is-invalid @enderror"
                                value="{{ $errors->hasBag($bag) ? old('name') : $edition->name }}">
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="name_en" maxlength="100" aria-label="{{ __('Name (englisch)') }}"
                                placeholder="{{ __('Name (englisch)') }}"
                                class="form-control form-control-sm @error('name_en', $bag) is-invalid @enderror"
                                value="{{ $errors->hasBag($bag) ? old('name_en') : $edition->name_en }}">
                        </div>
                        <div class="col-sm-4 d-flex align-items-center justify-content-end gap-2">
                            <a href="{{ route('records.index', ['edition' => $edition->id]) }}" class="small text-nowrap">{{ trans_choice(':count Platte|:count Platten', $edition->records_count) }}</a>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('Speichern') }}" aria-label="{{ __('Speichern') }}"><i class="bi bi-check-lg"></i></button>
                            <x-delete-button :action="route('editions.destroy', $edition)"
                                :message="trans_choice('Zusatzinfo „:name“ löschen? Sie wird bei :count Platte entfernt, die Platte selbst bleibt erhalten.|Zusatzinfo „:name“ löschen? Sie wird bei :count Platten entfernt, die Platten selbst bleiben erhalten.', $edition->records_count, ['name' => $edition->label])" />
                        </div>
                    </form>
                    @foreach ($errors->getBag($bag)->all() as $message)
                        <div class="text-danger small">{{ $message }}</div>
                    @endforeach
                </div>
            @empty
                <div class="card-body">{{ __('Noch keine Zusatzinfos angelegt.') }}</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

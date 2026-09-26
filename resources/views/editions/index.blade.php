<x-app-layout title="{{ __('Zusatzinfos') }}">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-1">{{ __('Zusatzinfos') }}</h1>
        <p class="text-body-secondary">{{ __('Zusätzliche Informationen wie „Boxset“ oder „Erstpressung“, die du bei jeder Platte auswählen kannst.') }}</p>

        <form method="POST" action="{{ route('editions.store') }}" class="card card-body mb-3">
            @csrf
            <label for="name" class="form-label">{{ __('Neue Zusatzinfo') }}</label>
            <div class="input-group">
                <input type="text" name="name" id="name" maxlength="100" required placeholder="{{ __('z. B. Promo') }}"
                    class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('Hinzufügen') }}</button>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </form>

        <div class="card">
            @forelse ($editions as $edition)
                <div class="card-body border-bottom py-2">
                    <form method="POST" action="{{ route('editions.update', $edition) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" maxlength="100" required aria-label="{{ __('Name') }}"
                            class="form-control form-control-sm @error('name', 'edition'.$edition->id) is-invalid @enderror"
                            value="{{ old('name', $edition->name) }}">
                        <a href="{{ route('records.index', ['edition' => $edition->id]) }}" class="small text-nowrap">{{ trans_choice(':count Platte|:count Platten', $edition->records_count) }}</a>
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('Umbenennen') }}" aria-label="{{ __('Umbenennen') }}"><i class="bi bi-check-lg"></i></button>
                        <x-delete-button :action="route('editions.destroy', $edition)"
                            :message="trans_choice('Zusatzinfo „:name“ löschen? Sie wird bei :count Platte entfernt, die Platte selbst bleibt erhalten.|Zusatzinfo „:name“ löschen? Sie wird bei :count Platten entfernt, die Platten selbst bleiben erhalten.', $edition->records_count, ['name' => $edition->name])" />
                    </form>
                    @error('name', 'edition'.$edition->id) <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            @empty
                <div class="card-body">{{ __('Noch keine Zusatzinfos angelegt.') }}</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

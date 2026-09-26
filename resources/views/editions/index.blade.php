<x-app-layout title="Zusatzinfos">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-1">Zusatzinfos</h1>
        <p class="text-body-secondary">Zusätzliche Informationen wie „Boxset“ oder „Erstpressung“, die du bei jeder Platte auswählen kannst.</p>

        <form method="POST" action="{{ route('editions.store') }}" class="card card-body mb-3">
            @csrf
            <label for="name" class="form-label">Neue Zusatzinfo</label>
            <div class="input-group">
                <input type="text" name="name" id="name" maxlength="100" required placeholder="z. B. Promo"
                    class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Hinzufügen</button>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </form>

        <div class="card">
            @forelse ($editions as $edition)
                <div class="card-body border-bottom py-2">
                    <form method="POST" action="{{ route('editions.update', $edition) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" maxlength="100" required aria-label="Name"
                            class="form-control form-control-sm @error('name', 'edition'.$edition->id) is-invalid @enderror"
                            value="{{ old('name', $edition->name) }}">
                        <a href="{{ route('records.index', ['edition' => $edition->id]) }}" class="small text-nowrap">{{ $edition->records_count }} {{ $edition->records_count === 1 ? 'Platte' : 'Platten' }}</a>
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Umbenennen" aria-label="Umbenennen"><i class="bi bi-check-lg"></i></button>
                        <x-delete-button :action="route('editions.destroy', $edition)"
                            :message="'Zusatzinfo „'.$edition->name.'“ löschen? Sie wird bei '.$edition->records_count.' Platte(n) entfernt, die Platten selbst bleiben erhalten.'" />
                    </form>
                    @error('name', 'edition'.$edition->id) <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            @empty
                <div class="card-body">Noch keine Zusatzinfos angelegt.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Neuer Künstler">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">Neuer Künstler</h1>
        <form action="{{ route('artists.store') }}" method="POST" class="card card-body">
            @csrf
            @include('artists._form', ['artist' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
                <a href="{{ route('artists.index') }}" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</x-app-layout>

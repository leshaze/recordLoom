<x-app-layout title="Neuer Anbieter">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">Neuer Anbieter</h1>
        <form action="{{ route('platforms.store') }}" method="POST" class="card card-body">
            @csrf
            @include('platforms._form', ['platform' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
                <a href="{{ route('platforms.index') }}" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</x-app-layout>

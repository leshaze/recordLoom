<x-app-layout title="Neues Label">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">Neues Label</h1>
        <form action="{{ route('labels.store') }}" method="POST" class="card card-body">
            @csrf
            @include('labels._form', ['label' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
                <a href="{{ route('labels.index') }}" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout :title="$label->name.' bearbeiten'">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">{{ $label->name }} <small class="text-body-secondary">bearbeiten</small></h1>
        <form action="{{ route('labels.update', $label) }}" method="POST" class="card card-body">
            @csrf
            @method('PUT')
            @include('labels._form')
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
                <a href="{{ route('labels.show', $label) }}" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</x-app-layout>

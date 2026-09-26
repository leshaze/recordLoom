<x-app-layout :title="$record->title.' bearbeiten'">
    <div class="container" style="max-width: 70rem;">
        <h1 class="h3 mb-3">{{ $record->title }} <small class="text-body-secondary">bearbeiten</small></h1>
        <form action="{{ route('records.update', $record) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('records._form')
            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
                <a href="{{ route('records.show', $record) }}" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</x-app-layout>

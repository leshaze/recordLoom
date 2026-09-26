<x-app-layout :title="$artist->name">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">{{ $artist->name }}</h1>
                <div class="text-body-secondary">
                    {{ $records->count() }} {{ $records->count() === 1 ? 'Platte' : 'Platten' }} · Wert {{ \App\Support\Format::euro($total_value ?: 0) }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('artists.print', $artist) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-pdf"></i> PDF</a>
                <a href="{{ route('artists.edit', $artist) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Bearbeiten</a>
                <x-delete-button :action="route('artists.destroy', $artist)" label="Löschen" :message="'Künstler „'.$artist->name.'“ wirklich löschen?'" />
            </div>
        </div>
        @if ($artist->description)
            <p style="white-space: pre-line;">{{ $artist->description }}</p>
        @endif
        @include('records._table', ['records' => $records])
    </div>
</x-app-layout>

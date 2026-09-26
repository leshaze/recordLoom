<x-app-layout :title="$platform->name">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">{{ $platform->name }}</h1>
                <div class="text-body-secondary">
                    {{ $records->count() }} {{ $records->count() === 1 ? 'Platte' : 'Platten' }} · Wert {{ \App\Support\Format::euro($total_value ?: 0) }}
                    @if ($platform->safe_url)
                        · <a href="{{ $platform->safe_url }}" target="_blank" rel="noopener noreferrer">{{ $platform->url }}</a>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('platforms.edit', $platform) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Bearbeiten</a>
                <x-delete-button :action="route('platforms.destroy', $platform)" label="Löschen" :message="'Anbieter „'.$platform->name.'“ wirklich löschen?'" />
            </div>
        </div>
        @if ($platform->description)
            <p style="white-space: pre-line;">{{ $platform->description }}</p>
        @endif
        @include('records._table', ['records' => $records])
    </div>
</x-app-layout>

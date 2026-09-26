<x-app-layout :title="$label->name">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">{{ $label->name }}</h1>
                <div class="text-body-secondary">
                    {{ trans_choice(':count Platte|:count Platten', $records->count()) }} · {{ __('Wert') }} {{ \App\Support\Format::euro($total_value ?: 0) }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('labels.print', $label) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-pdf"></i> {{ __('PDF') }}</a>
                <a href="{{ route('labels.edit', $label) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> {{ __('Bearbeiten') }}</a>
                <x-delete-button :action="route('labels.destroy', $label)" :label="__('Löschen')" :message="__('Label „:name“ wirklich löschen?', ['name' => $label->name])" />
            </div>
        </div>
        @if ($label->description)
            <p style="white-space: pre-line;">{{ $label->description }}</p>
        @endif
        @include('records._table', ['records' => $records])
    </div>
</x-app-layout>

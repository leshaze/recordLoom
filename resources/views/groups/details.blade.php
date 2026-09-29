{{-- Detail page of an artist, label or platform with its records. --}}
<x-app-layout :title="$item->name">
    <div class="container-fluid px-lg-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">{{ $item->name }}</h1>
                <div class="text-body-secondary">
                    {{ trans_choice(':count Platte|:count Platten', $recordCount) }} · {{ __('Wert') }} {{ \App\Support\Format::euro($totalValue ?: 0) }}
                    @if ($withUrl && $item->safe_url)
                        · <a href="{{ $item->safe_url }}" target="_blank" rel="noopener noreferrer">{{ $item->url }}</a>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                @if ($canPrint)
                    <a href="{{ route($resource.'.print', $item) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-pdf"></i> {{ __('PDF') }}</a>
                @endif
                <a href="{{ route($resource.'.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> {{ __('Bearbeiten') }}</a>
                <x-delete-button :action="route($resource.'.destroy', $item)" :label="__('Löschen')" :message="str_replace(':name', $item->name, $texts['confirm_delete'])" />
            </div>
        </div>
        @if ($item->description)
            <p style="white-space: pre-line;">{{ $item->description }}</p>
        @endif
        @include('records._list')
    </div>
</x-app-layout>

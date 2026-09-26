@props(['record', 'size' => 48, 'full' => false])

@if ($record->hasCover())
    <img src="{{ route('records.cover', ['record' => $record, 'thumb' => $full ? null : 1, 'v' => $record->updated_at?->timestamp]) }}"
        alt="Cover {{ $record->title }}" loading="lazy"
        {{ $attributes->merge(['class' => 'rounded object-fit-cover', 'style' => $full ? 'max-width: 100%;' : "width: {$size}px; height: {$size}px;"]) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded bg-body-tertiary d-flex align-items-center justify-content-center text-body-secondary', 'style' => $full ? 'aspect-ratio: 1; width: 100%;' : "width: {$size}px; height: {$size}px;"]) }}>
        <i class="bi bi-vinyl{{ $full ? ' fs-1' : '' }}"></i>
    </div>
@endif

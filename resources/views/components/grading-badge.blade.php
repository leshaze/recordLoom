@props(['grading'])

@if ($grading)
    <span class="badge text-bg-{{ $grading['color'] }}" title="{{ $grading['label'] }}">{{ $grading['german'] }}</span>
@endif

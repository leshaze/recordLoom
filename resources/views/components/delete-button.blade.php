@props(['action', 'message', 'label' => null])

{{-- Opens the confirmation dialog in the layout, which sends the DELETE request. --}}
<button type="button" {{ $attributes->merge(['class' => 'btn btn-sm btn-outline-danger']) }}
    data-bs-toggle="modal" data-bs-target="#deleteModal"
    data-delete-action="{{ $action }}" data-delete-message="{{ $message }}"
    title="{{ __('Löschen') }}" aria-label="{{ __('Löschen') }}">
    <i class="bi bi-trash"></i>@if ($label) {{ $label }}@endif
</button>

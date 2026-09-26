@props(['filter', 'sort'])

@php
    $active = $filter->values['sort'] === $sort;
    $icon = $active ? ($filter->values['dir'] === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25';
@endphp
<a href="{{ route('records.index', $filter->sortLink($sort)) }}" class="text-reset text-decoration-none text-nowrap">
    {{ $slot }} <i class="bi {{ $icon }}"></i>
</a>

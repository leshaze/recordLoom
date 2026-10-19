{{-- Text of a notification, built from its data in the current language. --}}
@php
    $data = $notification->data;
    $replace = ['title' => $data['title'] ?? '', 'artist' => $data['artist'] ?? '', 'seller' => $data['seller'] ?? ''];
    [$icon, $text] = match ($data['kind'] ?? null) {
        'sold_to_you' => ['bi-bag-check text-success', __('„:title“ von :artist wurde an dich verkauft.', $replace)],
        'sold_to_other' => ['bi-x-circle text-body-secondary', __('„:title“ von :artist wurde an jemand anderen verkauft.', $replace)],
        'interest' => ['bi-star-fill text-warning', __(':seller hat Interesse an „:title“ von :artist angemeldet.', $replace)],
        'interest_withdrawn' => ['bi-star text-body-secondary', __(':seller hat das Interesse an „:title“ von :artist zurückgenommen.', $replace)],
        default => ['bi-info-circle', __('Benachrichtigung')],
    };
@endphp
<i class="bi {{ $icon }}"></i>
@if (isset($data['record_id']) && \App\Models\Record::visible()->whereKey($data['record_id'])->exists())
    <a href="{{ route('records.show', $data['record_id']) }}">{{ $text }}</a>
@else
    {{ $text }}
@endif

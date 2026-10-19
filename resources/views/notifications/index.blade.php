<x-app-layout :title="__('Benachrichtigungen')">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-3">{{ __('Benachrichtigungen') }}</h1>
        @if ($notifications->isEmpty())
            <div class="card card-body text-body-secondary">{{ __('Keine Benachrichtigungen.') }}</div>
        @else
            <div class="list-group">
                @foreach ($notifications as $notification)
                    <div @class(['list-group-item d-flex gap-3 align-items-baseline', 'list-group-item-warning' => in_array($notification->id, $unreadIds, true)])>
                        <div class="flex-grow-1">@include('notifications._text')</div>
                        <span class="small text-body-secondary text-nowrap">{{ \App\Support\Format::date($notification->created_at) }} {{ $notification->created_at->format('H:i') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">{{ $notifications->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>

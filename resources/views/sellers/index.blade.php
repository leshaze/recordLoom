<x-app-layout :title="__('Verkäufer (Liste)')">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-3"><i class="bi bi-people"></i> {{ __('Verkäufer (Liste)') }}</h1>
        <div class="card">
            @if ($sellers->isEmpty())
                <div class="card-body text-body-secondary">{{ __('Es gibt noch keine Verkäufer. Ein Admin kann sie unter „Verwaltung → Benutzer“ einladen.') }}</div>
            @else
                @include('sellers._list')
            @endif
        </div>
    </div>
</x-app-layout>

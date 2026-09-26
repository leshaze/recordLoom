<x-app-layout title="{{ __('Neuer Anbieter') }}">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">{{ __('Neuer Anbieter') }}</h1>
        <form action="{{ route('platforms.store') }}" method="POST" class="card card-body">
            @csrf
            @include('platforms._form', ['platform' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ route('platforms.index') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

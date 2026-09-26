<x-app-layout title="{{ __('Neue Platte') }}">
    <div class="container" style="max-width: 70rem;">
        <h1 class="h3 mb-3">{{ __('Neue Platte') }}</h1>
        <form action="{{ route('records.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('records._form')
            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ route('records.index') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

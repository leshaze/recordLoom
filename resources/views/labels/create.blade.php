<x-app-layout title="{{ __('Neues Label') }}">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">{{ __('Neues Label') }}</h1>
        <form action="{{ route('labels.store') }}" method="POST" class="card card-body">
            @csrf
            @include('labels._form', ['label' => null])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ route('labels.index') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

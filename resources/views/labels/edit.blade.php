<x-app-layout :title="__(':name bearbeiten', ['name' => $label->name])">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">{{ $label->name }} <small class="text-body-secondary">{{ __('bearbeiten') }}</small></h1>
        <form action="{{ route('labels.update', $label) }}" method="POST" class="card card-body">
            @csrf
            @method('PUT')
            @include('labels._form')
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ route('labels.show', $label) }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

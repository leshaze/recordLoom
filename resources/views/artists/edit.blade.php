<x-app-layout :title="__(':name bearbeiten', ['name' => $artist->name])">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">{{ $artist->name }} <small class="text-body-secondary">{{ __('bearbeiten') }}</small></h1>
        <form action="{{ route('artists.update', $artist) }}" method="POST" class="card card-body">
            @csrf
            @method('PUT')
            @include('artists._form')
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ route('artists.show', $artist) }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

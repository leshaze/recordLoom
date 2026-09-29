{{-- Create and edit form of artists, labels and platforms. $item is null when creating. --}}
<x-app-layout :title="$item ? __(':name bearbeiten', ['name' => $item->name]) : $texts['new']">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">
            @if ($item)
                {{ $item->name }} <small class="text-body-secondary">{{ __('bearbeiten') }}</small>
            @else
                {{ $texts['new'] }}
            @endif
        </h1>
        <form action="{{ $item ? route($resource.'.update', $item) : route($resource.'.store') }}" method="POST" class="card card-body">
            @csrf
            @if ($item) @method('PUT') @endif
            <p class="small text-body-secondary mb-3">{!! __('Felder mit :star sind Pflichtfelder.', ['star' => '<span class="text-danger">*</span>']) !!}</p>
            <div class="mb-3">
                <label for="{{ $nameField }}" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="{{ $nameField }}" id="{{ $nameField }}" maxlength="255" required autofocus
                    class="form-control @error($nameField) is-invalid @enderror" value="{{ old($nameField, $item?->name) }}">
                @error($nameField) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @if ($withUrl)
                <div class="mb-3">
                    <label for="url" class="form-label">{{ __('URL') }}</label>
                    <input type="url" name="url" id="url" maxlength="255" placeholder="https://…"
                        class="form-control @error('url') is-invalid @enderror" value="{{ old('url', $item?->url) }}">
                    @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
            <div class="mb-3">
                <label for="description" class="form-label">{{ __('Beschreibung') }}</label>
                <textarea name="description" id="description" rows="4" maxlength="5000"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $item?->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ __('Speichern') }}</button>
                <a href="{{ $item ? route($resource.'.show', $item) : route($resource.'.index') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

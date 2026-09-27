<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <title>{{ isset($title) ? $title.' – ' : '' }}RecordLoom</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="d-flex flex-column min-vh-100" data-autocomplete-url="{{ route('autocomplete') }}">
    @include('layouts.navigation')

    @include('layouts.toasts')

    <main class="flex-grow-1 py-4">
        {{ $slot }}
    </main>

    <footer class="bg-black text-body-secondary text-center small py-2 mt-auto">
        RecordLoom © {{ now()->year }}
    </footer>

    <!-- Confirmation dialog for all delete buttons (see x-delete-button) -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="deleteModalForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteModalTitle">{{ __('Wirklich löschen?') }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Schließen') }}"></button>
                </div>
                <div class="modal-body" id="deleteModalText"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Abbrechen') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> {{ __('Löschen') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Barcode scanner, opened by buttons with data-barcode-scan (navigation search and Discogs search) -->
    <div class="modal fade" id="barcode-modal" tabindex="-1" aria-labelledby="barcode-modal-title" aria-hidden="true"
        data-collection-url="{{ route('records.barcode') }}"
        data-texts="{{ json_encode([
            'notFound' => __('Kein Barcode erkannt. Bitte näher heran, gerade halten und auf gutes Licht achten.'),
            'noCamera' => __('Die Kamera konnte nicht gestartet werden. Bitte den Zugriff auf die Kamera erlauben oder ein Foto aufnehmen.'),
            'reading' => __('Lese Barcode aus dem Foto …'),
        ]) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="barcode-modal-title"><i class="bi bi-upc-scan"></i> {{ __('Barcode scannen') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Schließen') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="position-relative bg-black rounded overflow-hidden" style="aspect-ratio: 4 / 3;">
                        <video id="barcode-video" class="w-100 h-100 object-fit-cover" muted playsinline></video>
                        {{-- Aiming frame --}}
                        <div class="position-absolute top-50 start-50 translate-middle border border-2 border-info rounded"
                            style="width: 80%; height: 35%; box-shadow: 0 0 0 100vmax rgba(0, 0, 0, .35);"></div>
                    </div>
                    <p class="small text-body-secondary mt-2 mb-0">{{ __('Halte den Barcode in den Rahmen. Er wird automatisch erkannt.') }}</p>
                    <p class="small text-danger mt-2 mb-0 d-none" id="barcode-error" role="alert"></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <label class="btn btn-outline-secondary mb-0">
                        <i class="bi bi-camera"></i> {{ __('Foto aufnehmen') }}
                        <input type="file" id="barcode-photo" accept="image/*" capture="environment" class="d-none">
                    </label>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Abbrechen') }}</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

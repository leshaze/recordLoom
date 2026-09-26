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
</body>

</html>

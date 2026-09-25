<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container d-flex flex-column align-items-center py-5">
            <a href="/" class="navbar-brand mb-4">
                {{ config('app.name', 'recordsArchive') }} <i class="bi bi-file-music-fill"></i>
            </a>

            <div class="card w-100 p-4" style="max-width: 28rem;">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

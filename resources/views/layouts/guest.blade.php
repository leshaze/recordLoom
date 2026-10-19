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
    <body class="d-flex flex-column min-vh-100">
        <main class="container d-flex flex-column align-items-center py-5 flex-grow-1">
            <div class="d-flex align-items-center justify-content-between w-100 mb-4" style="max-width: 26rem;">
                <span class="navbar-brand fs-4">RecordLoom <i class="bi bi-vinyl-fill"></i></span>
                <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Sprache') }}">
                    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $locale)
                        <a href="{{ route('language', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
                            @class(['btn', 'btn-secondary' => app()->getLocale() === $locale, 'btn-outline-secondary' => app()->getLocale() !== $locale])>{{ strtoupper($locale) }}</a>
                    @endforeach
                </div>
            </div>

            <div class="card w-100 p-4" style="max-width: 26rem;">
                @if (session('status'))
                    <div class="alert alert-success small" role="status">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </body>
</html>

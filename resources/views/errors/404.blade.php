<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Seite nicht gefunden') }} – RecordLoom</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="d-flex align-items-center min-vh-100">
    <main class="container text-center py-5">
        <div class="display-1 text-body-secondary"><i class="bi bi-vinyl"></i> 404</div>
        <h1 class="h3 mt-3">{{ __('Seite nicht gefunden') }}</h1>
        <p class="text-body-secondary">{{ __('Diese Seite gibt es leider nicht (mehr).') }}</p>
        <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i> {{ __('Zur Startseite') }}</a>
    </main>
</body>

</html>

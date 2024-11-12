<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'Laravel') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <!-- Scripts -->
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
  <main class="py-4 my-5">
    <div class="container">
      <div class="wrapper">
          <h1>404 <br> We can&rsquo;t find that page</h1>
          <div>
            <p>We&rsquo;re fairly sure that page used to be here, but seems to have gone missing. We do apologise on it&rsquo;s behalf.</p>
            <input type="button" class="btn btn-info" value="Home" onclick="window.location = '{{ url('/') }}'">

          </div>
      </div>
    </div>
  </main>
</body>

</html>
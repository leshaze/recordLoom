<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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
    <main class="m-2">
                <h2 class="text-center">{{ $artist->name }} - {{ $total_value }} €</h2>
                <div class="row">
                    <div class="col-sm-12">
                        @if(!empty($records) && $records->count())
                        @if($artist->description) <p>{{$artist->description}}
                        <p> @endif
                        <table class=" small table">
                            <tr>
                                <th>Kind</th>
                                <th>Title</th>
                                <th>Label</th>
                                <th>Cover</th>
                                <th>Media</th>
                                <th>Katalog-Nr.</th>
                                <th>Matrix-Nr.</th>
                                <th>Archiv-Nr.</th>
                                <th>Barcode</th>
                                <th>Aktueller Preis</th>
                                <th>Erscheinungsjahr</th>
                                <th>Herkunftsland</th>
                            </tr>
                            @foreach($records as $record)
                            <tr>
                                <td>{{ $record->kind }}</td>
                                <td>{{ $record->title }}</td>
                                <td>@if($record->label_id) {{ $record->label->name}} @endif</td>
                                <td>{{ $record->grading_cover}}</td>
                                <td>{{ $record->grading_media}}</td>
                                <td>{{ $record->catalog_number}}</td>
                                <td>{{ $record->matrix_number}}</td>
                                <td>{{ $record->archive_number}}</td>
                                <td>{{ $record->barcode}}</td>
                                <td>@if($record->current_price) {{ $record->current_price}} € @endif</td>
                                <td>{{ $record->release_date}}</td>
                                <td>{{ $record->country->name}}</td>
                            </tr>
                            @endforeach
                        </table>
                        @else
                        There is no data yet.
                        @endif
                    </div>

        </div>
    </main>
</body>
</html>
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

    <style>
        /* Margin Utility Classes */
        .m-2 {
            margin: 0.5rem !important;
        }

        /* Text Center */
        .text-center {
            text-align: center !important;
        }

        /* Row */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: 0;
            /* Einheitliche Ränder */
            margin-left: 0;
        }

        /* Column */
        .col-sm-12 {
            flex: 0 0 auto;
            width: 100%;
            padding-right: 0;
            padding-left: 0;
        }

        /* Table */
        table {
            caption-side: bottom;
            border-collapse: collapse;

        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            vertical-align: top;
        }

        .table> :not(caption)>*>* {
            padding: 0.5rem 0.5rem;
            border-bottom-width: 1px;
        }

        /* Small Text */
        .small {
            font-size: 0.875em;
        }

        tr {
            border-bottom: 1px solid #e0e0e0;
            /* Dünner, grauer Strich */

        }

        th,
        td {
            text-align: left;
            padding: 0.5rem;
            /* Einheitliches Padding */

        }
    </style>
</head>

<body>
    <main class="m-2">
        <h2 class="text-center">{{ __('Records') }}</h2>
        <div class="row">
            <div class="col-sm-12">
                @if(!empty($records) && $records->count())
                <table class=" small table">
                    <tr>
                        <th>Kind</th>
                        <th>Künstler</th>
                        <th>Title</th>
                        <th>Cover</th>
                        <th>Media</th>
                        <!-- <th>Katalog-Nr.</th> -->
                        <th>Matrix-Nr.</th>
                        <!-- <th>Archiv-Nr.</th> -->
                        <!-- <th>Barcode</th> -->
                        <!-- <th>Aktueller Preis</th> -->
                        <th>Erscheinungsjahr</th>
                        <th>Herkunftsland</th>
                        <th>Notiz</th>
                    </tr>
                    @foreach($records as $record)
                    <tr>
                        <td>{{ $record->kind }}</td>
                        <td>{{ $record->artist->name }}</td>
                        <td>{{ $record->title }}</td>
                        <td>{{ $record->grading_cover}}</td>
                        <td>{{ $record->grading_media}}</td>
                        <!-- <td>{{ $record->catalog_number}}</td> -->
                        <td>{{ $record->matrix_number}}</td>
                        <!-- <td>{{ $record->archive_number}}</td> -->
                        <!-- <td>{{ $record->barcode}}</td> -->
                        <!-- <td>@if($record->current_price) {{ $record->current_price}} € @endif</td> -->
                        <td>{{ $record->release_date}}</td>
                        <td>{{ $record->country->name}}</td>
                        <td>{{ $record->note }}</td>
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
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RecordLoom</title>

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
    <main>
        <h2 class="text-center">{{ $artist->name }} – {{ \App\Support\Format::euro($total_value ?: 0) }}</h2>
        <div class="row">
            <div class="col-sm-12">
                @if(!empty($records) && $records->count())
                @if($artist->description) <p>{{$artist->description}}
                @endif
                <table class="small table">
                    <tr>
                        <th>{{ __('Art') }}</th>
                        <th>{{ __('Titel') }}</th>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Cover') }}</th>
                        <th>{{ __('Media') }}</th>
                        <th>{{ __('Katalog-Nr.') }}</th>
                        <th>{{ __('Matrix-Nr.') }}</th>
                        <th>{{ __('Archiv-Nr.') }}</th>
                        <th>{{ __('Barcode') }}</th>
                        <th>{{ __('Aktueller Preis') }}</th>
                        <th>{{ __('Erscheinungsjahr') }}</th>
                        <th>{{ __('Herkunftsland') }}</th>
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
                        <td>{{ \App\Support\Format::euro($record->current_price) }}</td>
                        <td>{{ $record->release_year }}</td>
                        <td>{{ $record->country?->name }}</td>
                    </tr>
                    @endforeach
                </table>
                @else
                {{ __('Keine Einträge vorhanden.') }}
                @endif
            </div>
        </div>
    </main>
</body>
</html>
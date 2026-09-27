@props(['title', 'subtitle' => '', 'records'])
{{-- Shared layout of the PDF exports (dompdf, A4 landscape). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ $title }} – RecordLoom</title>
    <style>
        @page {
            margin: 12mm 10mm 14mm 10mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7.5pt;
            color: #222;
            line-height: 1.3;
        }

        h1 {
            font-size: 13pt;
            margin: 0 0 1mm 0;
        }

        .subtitle {
            color: #666;
            margin-bottom: 4mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        th {
            text-align: left;
            font-weight: bold;
            font-size: 7pt;
            color: #444;
            border-bottom: 1.2pt solid #444;
            padding: 1.5mm 1.2mm;
        }

        td {
            padding: 1.2mm;
            border-bottom: 0.5pt solid #ccc;
            vertical-align: top;
            word-wrap: break-word;
        }

        tr {
            page-break-inside: avoid;
        }

        .nowrap {
            white-space: nowrap;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #777;
        }

        .strong {
            font-weight: bold;
        }

        footer {
            position: fixed;
            bottom: -9mm;
            left: 0;
            right: 0;
            font-size: 6.5pt;
            color: #888;
        }

        footer .page:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <footer>
        RecordLoom · {{ \App\Support\Format::date(now()) }}
        <span style="float: right;">{{ __('Seite') }} <span class="page"></span></span>
    </footer>

    <h1>{{ $title }}</h1>
    <div class="subtitle">{{ $subtitle }}</div>

    @if ($records->isEmpty())
        <p>{{ __('Keine Einträge vorhanden.') }}</p>
    @else
        {{ $slot }}
    @endif
</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <title>{{ config('app.name', 'Laravel') }}</title>


    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
    @include('layouts.navigation')

    <!-- Page Content -->
    <main class="py-4 my-5">
        {{ $slot }}
    </main>
    <!-- Footer -->
    <footer class="bg-black text-center text-lg-start fixed-bottom">
        <!-- Copyright -->
        <div class="text-center p-1">
            © 2024 Copyright
        </div>
        <!-- Copyright -->
    </footer>

</body>
<script type="module">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const autocompleteUrl = @js(route('autocomplete'));

    // Autocomplete for a name field that stores the id of the selected entry in a hidden field.
    function autocompleteWithId(input, search, idField) {
        $(input).autocomplete({
            source: function(request, response) {
                $.getJSON(autocompleteUrl, { search: search, term: request.term }, function(data) {
                    response(data.map(row => ({ label: row.name, id: row.id })));
                });
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $(idField).val(ui.item.id);
            },
            change: function(event, ui) {
                if (ui.item == null) {
                    $(idField).val('');
                }
            }
        });
    }

    $(function() {
        $('#sold').on('click', function() {
            $('#sold_to, #sold_price, #sold_date, #sold_to_label, #sold_price_label, #sold_date_label').toggle();
        });

        const detailRoutes = {
            record: '/records/',
            artist: '/artists/',
            label: '/labels/',
        };

        $('#search').autocomplete({
            source: function(request, response) {
                $.getJSON(autocompleteUrl, { search: 'all', term: request.term }, function(data) {
                    response(data.map(row => ({ label: row.name, type: row.type, id: row.id })));
                });
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                if (detailRoutes[ui.item.type]) {
                    window.location.href = detailRoutes[ui.item.type] + encodeURIComponent(ui.item.id);
                }
            }
        });

        autocompleteWithId('#artist_name', 'artist', '#artist_id');
        autocompleteWithId('#label_name', 'label', '#label_id');
        autocompleteWithId('#country_name', 'country', '#country_id');
        autocompleteWithId('#platform', 'platform', '#platform_id');

        $('#title').autocomplete({
            source: function(request, response) {
                $.getJSON(autocompleteUrl, {
                    search: 'title',
                    term: request.term,
                    artist_id: $('#artist_id').val()
                }, function(data) {
                    response(data.map(row => ({
                        label: row.archive_number ? row.title + ' - Archiv.Nr.:' + row.archive_number : row.title,
                        value: row.title
                    })));
                });
            },
            minLength: 1,
            delay: 200
        });
    });
</script>

</html>
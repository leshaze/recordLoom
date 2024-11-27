<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

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
    /*     window.onload = function() {
             if (window.$) {
                 // jQuery is loaded  
                 console.log("jQuery has loaded!");
            } else {
                 // jQuery is not loaded
                 console.log("jQuery has not loaded!");
             }
        };  */
    $(document).ready(function() {

        $('#sold').click(function() {
            $('#sold_to').toggle();
            $('#sold_price').toggle();
            $('#sold_date').toggle();
            $('#sold_to_label').toggle();
            $('#sold_price_label').toggle();
            $('#sold_date_label').toggle();
        });

        $('#search').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=all&term=' + request.term,
                    function(data) {
                        var array = $.map(data, function(row) {
                            return {
                                //label: row[0].title || row[1].name | row[2].name,
                                //label: row.name + ' - ' + row.artist.name,
                                label: row.name,
                                type: row.type,
                                id: row.id
                            }

                        });
                        //console.log(array);
                        response($.ui.autocomplete.filter(array, request.term));
                    })
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                if (ui.item.type == "record") {
                    window.location.href = '/records/' + ui.item.id;
                }
                if (ui.item.type == "artist") {
                    window.location.href = '/artists/' + ui.item.id;
                }
                if (ui.item.type == "label") {
                    window.location.href = '/labels/' + ui.item.id;
                }
            }
        });

        $('#artist_name').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=artist&term=' + request.term,
                    function(data) {
                        var array = $.map(data, function(row) {
                            return {
                                label: row.name,
                                artist_id: row.id
                            }

                        })
                        console.log(array);
                        response($.ui.autocomplete.filter(array, request.term));
                    });
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $('#artist_id').val(ui.item.artist_id);
            },
            change: function(event, ui) {
                if (ui.item == null) {
                    $('#artist_id').val("")
                }
            }
        });
        $('#label_name').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=label&term=' + request.term,
                    function(data) {
                        var array = $.map(data, function(row) {
                            return {
                                label: row.name,
                                label_id: row.id
                            }
                        })
                        response($.ui.autocomplete.filter(array, request.term));
                    })
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $('#label_id').val(ui.item.label_id)
            },
            change: function(event, ui) {
                if (ui.item == null) {
                    $('#label_id').val("")
                }
            }
        });

        $('#title').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=title&term=' + request.term +
                    '_' + document.getElementById('artist_id').value,
                    function(data) {

                        var array = $.map(data, function(row) {
                            if (row.archive_number) {
                                return {
                                    label: row.title + ' - Archiv.Nr.:' + row.archive_number,
                                    value: row.title
                                }
                            } else {
                                return {
                                    label: row.title,
                                    value: row.title
                                }

                            }

                        })


                        response($.ui.autocomplete.filter(array, request.term));
                    })
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $('#title').val(ui.item.name)
            },
            change: function(event, ui) {
                if (ui.item == null) {

                }
            }
        });

        $('#country_name').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=country&term=' + request
                    .term,
                    function(data) {
                        var array = $.map(data, function(row) {
                            return {
                                label: row.name,
                                artist_id: row.id
                            }
                        })
                        response($.ui.autocomplete.filter(array, request.term));
                    })
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $('#country_id').val(ui.item.artist_id)
            },
            change: function(event, ui) {
                if (ui.item == null) {
                    $('#country_id').val("")
                }
            }
        });

        $('#platform_name').autocomplete({
            source: function(request, response) {
                $.getJSON('{{ route('autocomplete') }}' + '/?search=platform&term=' + request.term,
                    function(data) {
                        var array = $.map(data, function(row) {
                            return {
                                label: row.name,
                                label_id: row.id
                            }
                        })
                        response($.ui.autocomplete.filter(array, request.term));
                    })
            },
            minLength: 1,
            delay: 200,
            select: function(event, ui) {
                $('#platform_id').val(ui.item.label_id)
            },
            change: function(event, ui) {
                if (ui.item == null) {
                    $('#platform_id').val("")
                }
            }
        });



    });
</script>

</html>
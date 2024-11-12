<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card">
                <div class="card-header">{{ $record->title }} <a href="{{ route('records.edit', ['record' => $record->id]) }}"
                        class="btn btn-sm"><i class="bi bi-pencil-square"></i></a> <a
                        href="javascript:document.getElementById('delete-record-form').submit();" class="btn btn-sm"><i
                            class="bi bi-trash"
                            onclick="return confirm('Delete {{ $record->artist->name }} - {{ $record->title }}?')"></i></a>
                    <form id="delete-record-form" action="{{ route('records.destroy', ['record' => $record->id]) }}"
                        method="post" style="display: none;">
                        @method('DELETE')
                        {{ csrf_field() }}
                    </form>
                </div>
                <div class="row p-2">
                    <div class="col-sm-1">
                        <input class="form-check-input" type="radio" name="kind" id="Radios1" value="LP"
                            @if ($record->kind == 'LP') checked @endif disabled>
                        <label class="form-check-label" for="Radios1">
                            LP
                        </label><br>
                        <input class="form-check-input" type="radio" name="kind" id="Radios2" value="CD"
                            @if ($record->kind == 'CD') checked @endif disabled>
                        <label class="form-check-label" for="Radios2">
                            CD
                        </label>
                    </div>
                    <div class="col-sm-1">
                    @if ($record->sold) 
                        <input class="form-check-input" type="checkbox" id="sold" name="sold" checked disabled>
                        <label class="form-check-label" for="sold" >Verkauft</label>
                    @endif
                    @if ($record->lost)
                        <input class="form-check-input" type="checkbox" id="lost" name="lost" checked disabled>
                        <label class="form-check-label" for="lost" >Verloren</label>
                    @endif
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col-md-2">
                        <label for="title">Künstler</label>
                        <br><a href="{{ route('artists.show', ['artist' => $record->artist_id]) }}">
                            {{ $record->artist->name }}</a>
                    </div>
                    <div class="col-md-2">
                        <label for="title">Titel</label>
                        <br><a href="{{ route('records.show', ['record' => $record->id]) }}">
                            {{ $record->title }}</a>
                    </div>
                    <div class="col-md-2">
                        <label for="label_name">Label</label>
                        <br><a href="{{ route('labels.show', ['label' => $record->label_id]) }}">
                            {{ $record->label->name }}</a>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col-sm-2">
                        <label for="barcode">Barcode</label>
                        <br>{{ $record->barcode }}
                    </div>
                    <div class="col-sm-2">
                        <label for="title">Katalog-Nr.</label>
                        <br>{{ $record->catalog_number }}
                    </div>
                    <div class="col-sm-2">
                        <label for="matrix_number">Matrix-Nr.</label>
                        <br>{{ $record->matrix_number }}
                    </div>
                    <div class="col-sm-2">
                        <label for="archive_number">Archive-Nummer</label>
                        <br>{{ $record->archive_number }}
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col-sm-2">
                        <label for="country">Herkunftsland</label>
                        <br>{{ $record->country->name }}
                    </div>
                    <div class="col-sm-2">
                        <label for="release_date">Veröffentlichungsdat.</label>
                        <br>{{ $record->release_date }}
                    </div>
                    <div class="col-sm-2">
                        <label for="reissue_date">Datum Neuauflage</label>
                        <br>{{ $record->reissue_date }}
                    </div>
                    <div class="col-sm-2">
                        <label for="platform">Anbieter</label>
                        <br>{{ $record->platform->name }}
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col-sm-2">
                        <label for="grading_media">Grading media</label>
                        <br>{{ $record->grading_media }}
                    </div>
                    <div class="col-sm-2">
                        <label for="grading_cover">Grading Cover</label>
                        <br>{{ $record->grading_cover }}
                    </div>
                    <div class="col-sm-2">
                        <label for="current_price">Aktueller Preis</label>
                        <br>{{ $record->current_price }} €
                    </div>
                </div>

                @if ($record->sold)
                <div class="row p-2">
                    <div class="col-sm-2">
                        <label for="sold_date">Verkaufsdatum</label>
                        <br>{{ $record->sold_date }}
                    </div>
                    <div class="col-sm-2">
                        <label for="sold_to">Verkauf an</label>
                        <br>{{ $record->sold_to }}
                    </div>
                    <div class="col-sm-2">
                        <label for="sold_price">Verkaufspreis €</label>
                        <br>{{ $record->sold_price }}

                    </div>
                </div>
                @endif
                @if ($prices->count() >= '2')
                <div class="row p-2">
                    <div class="col-sm-3">
                        
                        <label for="price_history">Preisentwicklung in €</label><br>
                        
                        @foreach ($prices as $price)
                        {{ date('d.m.Y', strtotime($price->created_at)) }} - {{ $price->price }} € @if ($price->platform)
                        - <a href="{{ $price->platform->url }}" target="_blank">{{ $price->platform->name }}</a><br>
                        @endif
                        @endforeach
                    </div>
                    <div class="col-sm-4">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
                @endif    
            </div>
        </div>
    </div>
    <script type="module">
        
        const labels = [
            @foreach ($prices as $price)
                "{{ date('d.m.y', strtotime($price->created_at)) }}",
            @endforeach
        ];

        const data = {
            labels: labels,
            datasets: [{
                label: '',
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: [
                    @foreach ($prices as $price)
                        {{ $price->price }},
                    @endforeach
                ]
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        };

        const myChart = new Chart(
            document.getElementById('myChart'),
            config
        );
    </script>
</x-app-layout>
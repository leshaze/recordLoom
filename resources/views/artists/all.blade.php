<x-app-layout>
    <div class="container">
        <div class="wrapper">
            <!--             {{-- // Variante 1: langer Rahmen, Tabelle links
            <div class="card table-responsive">
                <div class="card-header">{{ __('Artists') }}</div>
                @if (!empty($artists) && $artists->count())
                <table class="table table-sm small d-inline-flex">
            // Variante 2: kleiner Rahmen, Tabelle mittig
            <div class="card table-responsive w-50 mx-auto"> 
                <div class="card-header">{{ __('Artists') }}</div>
                @if (!empty($artists) && $artists->count())
                <table class="table table-sm small" > 
            // Variante 3: großer Rahmen, Tabelle mittig
            <div class="card table-responsive"> 
                <div class="card-header">{{ __('Artists') }}</div>
                @if (!empty($artists) && $artists->count())
                <table class="table table-sm small w-auto mx-auto ">
            // Variante 4: kleiner Rahmen, Tabelle links
            <div class="card table-responsive"> 
                <div class="card-header">{{ __('Artists') }}</div>
                @if (!empty($artists) && $artists->count())
                <table class="table table-sm small w-auto mx-auto "> --}} -->

            <div class="card table-responsive">
                <div class="card-header">{{ __('Artists') }}</div>
                @if (!empty($artists) && $artists->count())
                <table class="table table-sm small d-inline-flex">
                    <tr>
                        <th style="min-width:100px;">Name</th>
                        <th style="min-width:80px;">Summe LP</th>
                        <th style="min-width:80px;">Summe CD</th>
                        <th style="min-width:100px;">Description</th>

                    </tr>
                    @foreach ($artists as $artist)
                    <tr>
                        <th align="left">
                            <a href="{{ route('artists.show', $artist->id) }}">{{ $artist->name }} </a>
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('artist_id', $artist->id)->where('kind', 'LP')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('artist_id', $artist->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('artist_id', $artist->id)->where('kind', 'CD')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('artist_id', $artist->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th>
                            @if ($artist->description)
                            {{ $artist->description }}
                            @endif
                        </th>
                    </tr>
                    @endforeach
                </table>
                @else
                There is no data yet.
                @endif
            </div>
            {{ $artists->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>
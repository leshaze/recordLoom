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
                        <th>Name</th>
                        <th>Summe LP</th>
                        <th>Summe CD</th>
                        <th>Description</th>

                    </tr>
                    @foreach ($artists as $artist)
                    <tr>
                        <td align="left">
                            <a href="{{ route('artists.show', $artist->id) }}">{{ $artist->name }} </a>
                        </td>
                        <td style="text-align: center;">
                            {{ $records->where('artist_id', $artist->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €

                        </td>
                        <td style="text-align: center;">
                            {{ $records->where('artist_id', $artist->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                        </td>
                        <td>
                            @if ($artist->description)
                            {{ $artist->description }}
                            @endif
                        </td>
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
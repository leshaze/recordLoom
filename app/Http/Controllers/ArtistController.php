<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\Artist;
use App\Models\Record;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ArtistController extends Controller
{
    public function index()
    {
        $artists = Artist::orderBy('name')->paginate(20);
        $records = Record::whereIn('artist_id', $artists->pluck('id'))->get(['artist_id', 'kind', 'current_price']);

        return view('artists.all', ['artists' => $artists, 'records' => $records]);
    }

    public function show(Artist $artist)
    {
        $records = $artist->records()->with(['artist', 'label', 'editions'])->get();
        $total_value = $records->sum('current_price');

        return view('artists.details', ['artist' => $artist, 'records' => $records, 'total_value' => $total_value]);
    }

    public function create()
    {
        return view('artists.create');
    }

    public function store(StoreArtistRequest $request)
    {
        $name = $request->validated('artist_name');

        if (Artist::where('name', $name)->exists()) {
            return redirect()->route('artists.create')->with('error', __('Künstler „:name“ ist bereits vorhanden.', ['name' => $name]));
        }

        $artist = new Artist;
        $artist->name = $name;
        $artist->description = $request->validated('description');
        $artist->save();

        return redirect()->route('artists.create')->with('info', __('Künstler „:name“ wurde angelegt.', ['name' => $artist->name]));
    }

    public function edit(Artist $artist)
    {
        return view('artists.edit', ['artist' => $artist]);
    }

    public function update(UpdateArtistRequest $request, Artist $artist)
    {
        $artist->name = $request->validated('artist_name');
        $artist->description = $request->validated('description');
        $artist->save();

        return redirect()->route('artists.index')->with('info', __('Künstler „:name“ wurde gespeichert.', ['name' => $artist->name]));
    }

    public function destroy(Artist $artist)
    {
        if ($artist->records()->exists()) {
            return redirect()->route('artists.index')->with('error', __('Künstler „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.', ['name' => $artist->name]));
        }

        $artist->delete();

        return redirect()->route('artists.index')->with('info', __('Künstler „:name“ wurde gelöscht.', ['name' => $artist->name]));
    }

    public function print(Artist $artist)
    {
        $current = Carbon::now()->format('d.m.Y');

        $records = $artist->records()->with(['label', 'country'])
            ->orderBy('title', 'ASC')
            ->get();

        $total_value = $records->sum('current_price');
        $pdf = Pdf::loadView('artists.print', ['artist' => $artist, 'records' => $records, 'total_value' => $total_value]);

        return $pdf->stream($artist->name.'-'.$current.'.pdf');
    }
}

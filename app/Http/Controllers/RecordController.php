<?php

namespace App\Http\Controllers;
use App\Models\Record;
use App\Models\Artist;

use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function index()
    {
        //Show all Records from the database and return to view
        // $records = Record::join('artists', 'records.artist_id', '=', 'artists.id')
        //     ->select('records.*')
        //     ->orderBy('artists.name', 'ASC')
        //     ->paginate(10);
        $records = Record::paginate(10);

        //dd($records);
        return view('records.all', ['records' => $records]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Record  $record
     * @return \Illuminate\Http\Response
     */
    public function show(Artist $request)
    {
        $term = $request->term;
        error_log($request);
        if ($request->search == 'artist') {
            if ($term) {
                return Artist::where('name', 'like', '%' . $term . '%')->get();
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;

class PlatformController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Show all platforms from the database and return to view
        $platforms = Platform::paginate(20);
        $records = Record::all();
        return view('platforms.all', ['platforms' => $platforms, 'records' => $records]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('platforms.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePlatformRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePlatformRequest $request)
    {
        //check the input
        $this->validate($request, [
            'platform_name' => 'required'
        ]);

        $get_platform = Platform::where('name', '=', $request->input('platform_name'))->first();

        if ($get_platform) {
            return redirect()->route('platforms.create')->with('error', 'Platform ' . $request->input('platform_name') . ' is already in the database');
        }
        if (!$get_platform) {
            $platform = new Platform();
            $platform->name = $request->input('platform_name');
            $platform->url = $request->input('url');
            $platform->descripion = $request->input('description');
            $platform->save(); //persist the data
            return redirect()->route('platforms.create')->with('info', 'Platform ' . $platform->name . '  added successfully');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Platform  $platform
     * @return \Illuminate\Http\Response
     */
    public function show(Platform $platform)
    {
        //Return view to detail label
        $platform = Platform::find($platform->id);
        $records = Record::with(['platform'])->where('platform_id', '=', $platform->id)->get();
        $total_value = Record::where('platform_id', '=', $platform->id)->sum('current_price');

        return view('platforms.details', ['platform' => $platform, 'records' => $records, 'total_value' => $total_value]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Platform  $platform
     * @return \Illuminate\Http\Response
     */
    public function edit(Platform $platform)
    {
        if (empty($platform)) {
            return redirect()->route('platforms.all')->with('error', 'Invalid Platform');
        } else {
            return view('platforms.edit', ['platform' => $platform]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePlatformRequest  $request
     * @param  \App\Models\Platform  $platform
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePlatformRequest $request, Platform $platform)
    {
        //check the input
        $this->validate($request, [
            'platform_name' => 'required'
        ]);
        
        $platform->id = $request->input('id');
        $platform->name = $request->input('platform_name');
        $platform->url = $request->input('url');
        $platform->description = $request->input('description');

        $platform->save();

        return redirect()->route('platforms.index')->with('info', 'Platform ' . $platform->name . ' updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Platform  $platform
     * @return \Illuminate\Http\Response
     */
    public function destroy(Platform $platform)
    {
        $platform = Platform::find($platform->id);
        $result = Record::where('platform_id', '=', $platform->id)->first();
        if (!$result) {

            //delete
            $platform->delete();
            return redirect()->route('platforms.index')->with('info', 'Platform ' . $platform->name . ' deleted successfully');
        } else {
            return redirect()->route('platforms.index')->with('error', 'Platform ' . $platform->name . ' could not be deleted. ');
        }
    }
}

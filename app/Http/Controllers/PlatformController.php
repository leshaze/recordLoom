<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Models\Platform;
use App\Models\Record;

class PlatformController extends Controller
{
    public function index()
    {
        $platforms = Platform::orderBy('name')->paginate(20);
        $records = Record::whereIn('platform_id', $platforms->pluck('id'))->get(['platform_id', 'kind', 'current_price']);

        return view('platforms.all', ['platforms' => $platforms, 'records' => $records]);
    }

    public function create()
    {
        return view('platforms.create');
    }

    public function store(StorePlatformRequest $request)
    {
        $name = $request->validated('platform_name');

        if (Platform::where('name', $name)->exists()) {
            return redirect()->route('platforms.create')->with('error', 'Platform '.$name.' is already in the database');
        }

        $platform = new Platform;
        $platform->name = $name;
        $platform->url = $request->validated('url');
        $platform->description = $request->validated('description');
        $platform->save();

        return redirect()->route('platforms.create')->with('info', 'Platform '.$platform->name.'  added successfully');
    }

    public function show(Platform $platform)
    {
        $records = $platform->records()->with(['artist', 'label'])->get();
        $total_value = $records->sum('current_price');

        return view('platforms.details', ['platform' => $platform, 'records' => $records, 'total_value' => $total_value]);
    }

    public function edit(Platform $platform)
    {
        return view('platforms.edit', ['platform' => $platform]);
    }

    public function update(UpdatePlatformRequest $request, Platform $platform)
    {
        $platform->name = $request->validated('platform_name');
        $platform->url = $request->validated('url');
        $platform->description = $request->validated('description');
        $platform->save();

        return redirect()->route('platforms.index')->with('info', 'Platform '.$platform->name.' updated successfully');
    }

    public function destroy(Platform $platform)
    {
        if ($platform->records()->exists()) {
            return redirect()->route('platforms.index')->with('error', 'Platform '.$platform->name.' could not be deleted. ');
        }

        $platform->delete();

        return redirect()->route('platforms.index')->with('info', 'Platform '.$platform->name.' deleted successfully');
    }
}

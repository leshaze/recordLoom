<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Models\Platform;
use App\Support\GroupFilter;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    public function index(Request $request)
    {
        $filter = new GroupFilter($request, Platform::class, 'platforms.index', ['name', 'description', 'url']);
        $platforms = $filter->query()->paginate($filter->values['per_page'])->withQueryString();

        return view('platforms.all', ['platforms' => $platforms, 'filter' => $filter]);
    }

    public function create()
    {
        return view('platforms.create');
    }

    public function store(StorePlatformRequest $request)
    {
        $name = $request->validated('platform_name');

        if (Platform::where('name', $name)->exists()) {
            return redirect()->route('platforms.create')->with('error', __('Anbieter „:name“ ist bereits vorhanden.', ['name' => $name]));
        }

        $platform = new Platform;
        $platform->name = $name;
        $platform->url = $request->validated('url');
        $platform->description = $request->validated('description');
        $platform->save();

        return redirect()->route('platforms.create')->with('info', __('Anbieter „:name“ wurde angelegt.', ['name' => $platform->name]));
    }

    public function show(Platform $platform)
    {
        $records = $platform->records()->with(['artist', 'label', 'editions'])->get();
        $total_value = $records->where('sold', false)->sum('current_price');

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

        return redirect()->route('platforms.index')->with('info', __('Anbieter „:name“ wurde gespeichert.', ['name' => $platform->name]));
    }

    public function destroy(Platform $platform)
    {
        if ($platform->records()->exists()) {
            return redirect()->route('platforms.index')->with('error', __('Anbieter „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.', ['name' => $platform->name]));
        }

        $platform->delete();

        return redirect()->route('platforms.index')->with('info', __('Anbieter „:name“ wurde gelöscht.', ['name' => $platform->name]));
    }
}

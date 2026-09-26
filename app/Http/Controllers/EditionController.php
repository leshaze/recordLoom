<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Maintains the list of "Zusatzinfos" (e.g. Boxset, Erstpressung) that can be assigned to records.
 */
class EditionController extends Controller
{
    public function index()
    {
        return view('editions.index', ['editions' => Edition::withCount('records')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:100', 'unique:editions,name']],
            [],
            ['name' => 'Name'],
        );

        $edition = Edition::create($data);

        return redirect()->route('editions.index')->with('info', 'Zusatzinfo „'.$edition->name.'“ wurde angelegt.');
    }

    public function update(Request $request, Edition $edition)
    {
        $data = $request->validateWithBag('edition'.$edition->id, [
            'name' => ['required', 'string', 'max:100', Rule::unique('editions', 'name')->ignore($edition)],
        ], [], ['name' => 'Name']);

        $edition->update($data);

        return redirect()->route('editions.index')->with('info', 'Zusatzinfo „'.$edition->name.'“ wurde gespeichert.');
    }

    public function destroy(Edition $edition)
    {
        $edition->delete();

        return redirect()->route('editions.index')->with('info', 'Zusatzinfo „'.$edition->name.'“ wurde gelöscht.');
    }
}

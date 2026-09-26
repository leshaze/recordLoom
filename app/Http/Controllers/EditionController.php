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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:editions,name'],
            'name_en' => ['nullable', 'string', 'max:100'],
        ], [], $this->attributeNames());

        $edition = Edition::create($data);

        return redirect()->route('editions.index')->with('info', __('Zusatzinfo „:name“ wurde angelegt.', ['name' => $edition->label]));
    }

    public function update(Request $request, Edition $edition)
    {
        $data = $request->validateWithBag('edition'.$edition->id, [
            'name' => ['required', 'string', 'max:100', Rule::unique('editions', 'name')->ignore($edition)],
            'name_en' => ['nullable', 'string', 'max:100'],
        ], [], $this->attributeNames());

        $edition->update($data);

        return redirect()->route('editions.index')->with('info', __('Zusatzinfo „:name“ wurde gespeichert.', ['name' => $edition->label]));
    }

    public function destroy(Edition $edition)
    {
        $edition->delete();

        return redirect()->route('editions.index')->with('info', __('Zusatzinfo „:name“ wurde gelöscht.', ['name' => $edition->label]));
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return ['name' => __('Name (deutsch)'), 'name_en' => __('Name (englisch)')];
    }
}

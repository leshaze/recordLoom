<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Label;
use App\Support\GroupFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Request $request)
    {
        $filter = new GroupFilter($request, Label::class, 'labels.index');
        $labels = $filter->query()->paginate($filter->values['per_page'])->withQueryString();

        return view('labels.all', ['labels' => $labels, 'filter' => $filter]);
    }

    public function create()
    {
        return view('labels.create');
    }

    public function store(StoreLabelRequest $request)
    {
        $name = $request->validated('label_name');

        if (Label::where('name', $name)->exists()) {
            return redirect()->route('labels.create')->with('error', __('Label „:name“ ist bereits vorhanden.', ['name' => $name]));
        }

        $label = new Label;
        $label->name = $name;
        $label->description = $request->validated('description');
        $label->save();

        return redirect()->route('labels.create')->with('info', __('Label „:name“ wurde angelegt.', ['name' => $label->name]));
    }

    public function show(Label $label)
    {
        $records = $label->records()->with(['artist', 'label', 'editions'])->get();
        $total_value = $records->sum('current_price');

        return view('labels.details', ['label' => $label, 'records' => $records, 'total_value' => $total_value]);
    }

    public function edit(Label $label)
    {
        return view('labels.edit', ['label' => $label]);
    }

    public function update(UpdateLabelRequest $request, Label $label)
    {
        $label->name = $request->validated('label_name');
        $label->description = $request->validated('description');
        $label->save();

        return redirect()->route('labels.index')->with('info', __('Label „:name“ wurde gespeichert.', ['name' => $label->name]));
    }

    public function destroy(Label $label)
    {
        if ($label->records()->exists()) {
            return redirect()->route('labels.index')->with('error', __('Label „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.', ['name' => $label->name]));
        }

        $label->delete();

        return redirect()->route('labels.index')->with('info', __('Label „:name“ wurde gelöscht.', ['name' => $label->name]));
    }

    public function print(Label $label)
    {
        $current = Carbon::now()->format('d.m.Y');

        $records = $label->records()
            ->select('records.*')
            ->with(['artist', 'country', 'editions'])
            ->join('artists', 'records.artist_id', '=', 'artists.id')
            ->orderBy('artists.name', 'ASC')
            ->orderBy('records.title', 'ASC')
            ->get();

        $total_value = $records->sum('current_price');
        $pdf = Pdf::loadView('labels.print', ['label' => $label, 'records' => $records, 'total_value' => $total_value]);

        return $pdf->stream($label->name.'-'.$current.'.pdf');
    }
}

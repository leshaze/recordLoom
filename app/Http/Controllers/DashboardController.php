<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('dashboard', [
            'count' => Record::count(),
            'total_value' => Record::sum('current_price'),
            'count_lp' => Record::where('kind', 'LP')->count(),
            'count_cd' => Record::where('kind', 'CD')->count(),
            'count_artist' => Artist::count(),
            'count_label' => Label::count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $inStock = Record::where('sold', false)->where('lost', false);

        return view('dashboard', [
            'count' => (clone $inStock)->count(),
            'total_value' => (clone $inStock)->sum('current_price'),
            'count_lp' => (clone $inStock)->where('kind', 'LP')->count(),
            'count_cd' => (clone $inStock)->where('kind', 'CD')->count(),
            'count_selling' => Record::where('selling', true)->where('sold', false)->count(),
            'count_sold' => Record::where('sold', true)->count(),
            'count_artist' => Artist::count(),
            'count_label' => Label::count(),
            'latest' => Record::with('artist')->latest()->latest('id')->take(8)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use App\Support\CollectionStats;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $inStock = CollectionStats::inStock();

        return view('dashboard', [
            'count' => (clone $inStock)->count(),
            'total_value' => (clone $inStock)->sum('current_price'),
            'count_lp' => (clone $inStock)->where('kind', 'LP')->count(),
            'count_cd' => (clone $inStock)->where('kind', 'CD')->count(),
            'count_selling' => Record::where('selling', true)->where('sold', false)->count(),
            'count_sold' => Record::where('sold', true)->count(),
            'count_artist' => Artist::count(),
            'count_label' => Label::count(),
            'latest' => Record::with('artist')->latest()->latest('id')->take(6)->get(),
            'value_by_month' => CollectionStats::valueByMonth(),
            'top_artists' => CollectionStats::top(Artist::class),
            'top_labels' => CollectionStats::top(Label::class),
            'gradings' => CollectionStats::gradings(),
            'years' => CollectionStats::years(),
        ]);
    }
}

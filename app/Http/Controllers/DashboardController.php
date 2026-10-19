<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use App\Support\CollectionStats;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if ($user->isSeller()) {
            $records = $user->assignedRecords()->with(['artist', 'label', 'editions'])->orderByDesc('record_seller.created_at')->get();

            return view('dashboard-seller', [
                'open' => $records->where('sold', false),
                'interested' => $records->where('sold', false)->whereNotNull('pivot.interested_at')->count(),
                'bought' => $records->where('sold_to_user_id', $user->id),
                'notifications' => $user->notifications()->latest()->take(5)->get(),
            ]);
        }

        $inStock = CollectionStats::inStock();

        return view('dashboard', [
            'count' => (clone $inStock)->count(),
            'total_value' => (clone $inStock)->sum('current_price'),
            'count_lp' => (clone $inStock)->where('kind', 'LP')->count(),
            'count_cd' => (clone $inStock)->where('kind', 'CD')->count(),
            'count_selling' => Record::visible()->where('selling', true)->where('sold', false)->count(),
            'count_sold' => Record::visible()->where('sold', true)->count(),
            'count_artist' => Artist::count(),
            'count_label' => Label::count(),
            'latest' => Record::visible()->with('artist')->latest()->latest('id')->take(6)->get(),
            'value_by_month' => CollectionStats::valueByMonth(),
            'top_artists' => CollectionStats::top(Artist::class),
            'top_labels' => CollectionStats::top(Label::class),
            'gradings' => CollectionStats::gradings(),
            'years' => CollectionStats::years(),
            'balance' => CollectionStats::balance(),
        ]);
    }
}

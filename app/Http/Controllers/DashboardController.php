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
            // Records the seller can see, with the own interest. Counted by the database: a seller who sees
            // all records would otherwise load the whole collection.
            $open = Record::visible()->where('sold', false);

            return view('dashboard-seller', [
                'open' => (clone $open)->with(['artist', 'label', 'editions', 'sellers' => fn ($query) => $query->whereKey($user->id)])
                    ->latest()->latest('id')->take(10)->get(),
                'openCount' => (clone $open)->count(),
                'interested' => (clone $open)->whereHas('sellers', fn ($query) => $query->whereKey($user->id)->whereNotNull('record_seller.interested_at'))->count(),
                'boughtCount' => Record::where('sold_to_user_id', $user->id)->count(),
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
            'sellers' => SellerController::withInterest($user)->get(),
        ]);
    }
}

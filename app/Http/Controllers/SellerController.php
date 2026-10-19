<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * For owners and admins: the sellers with the number of records they are interested in, and per seller
 * these records with the offers, compared with the offers of the other sellers.
 */
class SellerController extends Controller
{
    /**
     * Sellers with the number of records (of the viewer, not sold) they registered interest in and the sum of their offers;
     * most interest first.
     */
    public static function withInterest(User $viewer): Builder
    {
        $interest = fn (Builder $query) => $query->visibleTo($viewer)->where('records.sold', false)->whereNotNull('record_seller.interested_at');

        return User::where('role', Role::Seller->value)
            ->withCount(['assignedRecords as interest_count' => $interest])
            ->withSum(['assignedRecords as offer_sum' => $interest], 'record_seller.offer_price')
            ->orderByDesc('interest_count')
            ->orderBy('name');
    }

    public function index(Request $request): View
    {
        return view('sellers.index', ['sellers' => self::withInterest($request->user())->get()]);
    }

    public function show(Request $request, User $seller): View
    {
        abort_unless($seller->isSeller(), 404);

        // Records of the viewer the seller is interested in, with all interested sellers to compare the offers.
        $records = Record::visible()
            ->where('sold', false)
            ->whereHas('sellers', fn (Builder $query) => $query->whereKey($seller->id)->whereNotNull('record_seller.interested_at'))
            ->with(['artist', 'label', 'sellers' => fn ($query) => $query->wherePivotNotNull('interested_at')])
            ->orderBy('title')
            ->get();

        $rows = $records->map(function (Record $record) use ($seller) {
            $own = $record->sellers->firstWhere('id', $seller->id);
            $others = $record->sellers->reject(fn (User $other) => $other->id === $seller->id)->values();
            $best = $others->filter(fn (User $other) => $other->pivot->offer_price !== null)
                ->sortByDesc(fn (User $other) => (float) $other->pivot->offer_price)->first();

            return ['record' => $record, 'seller' => $own, 'others' => $others, 'best' => $best];
        });

        return view('sellers.show', [
            'seller' => $seller,
            'rows' => $rows,
            'offerSum' => $rows->sum(fn (array $row) => (float) $row['seller']->pivot->offer_price),
            'valueSum' => $records->sum(fn (Record $record) => (float) $record->current_price),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Record;
use App\Models\User;
use App\Notifications\InterestChanged;
use App\Notifications\RecordSold;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Records for sellers: the owner assigns sellers, sellers register interest, the owner sells to one of them.
 */
class RecordSaleController extends Controller
{
    public function sellers(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('update', $record);
        $ids = array_map('intval', $request->validate([
            'sellers' => ['array'],
            'sellers.*' => ['integer', Rule::exists('users', 'id')->where('role', Role::Seller->value)],
        ])['sellers'] ?? []);

        foreach ($record->sellers()->get() as $seller) {
            if (in_array($seller->id, $ids, true)) {
                continue;
            }
            // No longer assigned: a registered interest stays, otherwise the seller is removed.
            $seller->pivot->interested_at
                ? $record->sellers()->updateExistingPivot($seller->id, ['assigned' => false])
                : $record->sellers()->detach($seller->id);
        }
        foreach ($ids as $id) {
            $record->sellers()->syncWithoutDetaching([$id => ['assigned' => true]]);
        }

        return back()->with('info', __('Die Verkäufer wurden gespeichert.'));
    }

    /**
     * "Zum Verkauf vormerken": the record is shown to all sellers.
     */
    public function selling(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('update', $record);
        abort_if($record->sold, 422, __('Die Platte ist bereits verkauft.'));

        $record->selling = $request->boolean('selling');
        $record->save();

        return back()->with('info', $record->selling
            ? __('Die Platte ist zum Verkauf vorgemerkt und für alle Verkäufer sichtbar.')
            : __('Die Platte ist nicht mehr zum Verkauf vorgemerkt.'));
    }

    public function interest(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('interest', $record);
        $interested = $request->boolean('interested');
        $seller = $request->user();
        $assignment = $record->sellers()->whereKey($seller->id)->first()?->pivot;

        if ($interested) {
            // Sellers of a record marked for selling get a row without being assigned.
            $record->sellers()->syncWithoutDetaching([$seller->id => ['interested_at' => now(), 'assigned' => $assignment->assigned ?? false]]);
        } elseif ($assignment && ! $assignment->assigned) {
            $record->sellers()->detach($seller->id);
        } elseif ($assignment) {
            $record->sellers()->updateExistingPivot($seller->id, ['interested_at' => null]);
        }
        $record->owner?->notify(new InterestChanged($record, $seller, $interested));

        return back()->with('info', $interested
            ? __('Dein Interesse wurde angemeldet.')
            : __('Dein Interesse wurde zurückgenommen.'));
    }

    public function sell(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('update', $record);
        abort_if($record->sold, 422, __('Die Platte ist bereits verkauft.'));

        if (is_string($request->input('sold_price'))) {
            $request->merge(['sold_price' => str_replace(',', '.', trim($request->input('sold_price')))]);
        }
        $data = $request->validate([
            'seller_id' => ['required', 'integer'],
            'sold_price' => ['nullable', 'numeric', 'min:0'],
            'sold_on' => ['required', 'date'],
        ], [], ['sold_price' => __('Verkaufspreis'), 'sold_on' => __('Verkaufsdatum')]);

        $interested = $record->sellers()->wherePivotNotNull('interested_at')->get();
        $buyer = $interested->firstWhere('id', (int) $data['seller_id']);
        abort_if($buyer === null, 422, __('Dieser Verkäufer hat kein Interesse angemeldet.'));

        DB::transaction(function () use ($record, $data, $buyer) {
            $record->sold = true;
            $record->selling = false;
            $record->sold_on = $data['sold_on'];
            $record->sold_price = filled($data['sold_price'] ?? null) ? $data['sold_price'] : null;
            $record->sold_to = $buyer->name;
            $record->sold_to_user_id = $buyer->id;
            $record->save();
        });

        $mailFailed = false;
        foreach ($interested as $seller) {
            $mailFailed = ! $this->notify($seller, new RecordSold($record, $seller->is($buyer))) || $mailFailed;
        }

        return redirect()->route('records.show', $record)
            ->with('info', __('Die Platte wurde an :name verkauft. Alle Interessenten wurden benachrichtigt.', ['name' => $buyer->name]))
            ->with('warning', $mailFailed ? __('Die Benachrichtigung steht in der App, die E-Mail konnte aber nicht verschickt werden.') : null);
    }

    /**
     * The notification is stored in the app first; a failing mail server must not stop the sale.
     */
    private function notify(User $user, RecordSold $notification): bool
    {
        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}

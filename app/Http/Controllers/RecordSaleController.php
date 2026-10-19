<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Record;
use App\Models\User;
use App\Notifications\InterestChanged;
use App\Notifications\RecordSold;
use App\Notifications\RecordWithdrawn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
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
     * "Zum Verkauf vormerken": the record is shown to all sellers. Switching it off clears all interest and offers;
     * the interested sellers are told that the record is no longer for sale.
     */
    public function selling(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('update', $record);
        abort_if($record->sold, 422, __('Die Platte ist bereits verkauft.'));

        $record->selling = $request->boolean('selling');
        $interested = $record->selling ? collect() : $record->sellers()->wherePivotNotNull('interested_at')->get();

        DB::transaction(function () use ($record, $interested) {
            $record->save();
            // Assigned sellers keep their assignment, the others lose their access with the interest.
            foreach ($interested as $seller) {
                $seller->pivot->assigned
                    ? $record->sellers()->updateExistingPivot($seller->id, ['interested_at' => null, 'offer_price' => null])
                    : $record->sellers()->detach($seller->id);
            }
        });

        $mailFailed = false;
        foreach ($interested as $seller) {
            $mailFailed = ! $this->notify($seller, new RecordWithdrawn($record)) || $mailFailed;
        }

        return back()
            ->with('info', match (true) {
                $record->selling => __('Die Platte ist zum Verkauf vorgemerkt und für alle Verkäufer sichtbar.'),
                $interested->isNotEmpty() => __('Die Platte ist nicht mehr zum Verkauf vorgemerkt. Die Interessenten wurden benachrichtigt.'),
                default => __('Die Platte ist nicht mehr zum Verkauf vorgemerkt.'),
            })
            ->with('warning', $mailFailed ? __('Die Benachrichtigung steht in der App, die E-Mail konnte aber nicht verschickt werden.') : null);
    }

    /**
     * A seller registers interest, optionally with the price they offer, changes the offer, or withdraws.
     */
    public function interest(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('interest', $record);
        $offer = $this->price($request, 'offer_price', __('Dein Preisvorschlag'));
        $interested = $request->boolean('interested');
        $seller = $request->user();
        $assignment = $record->sellers()->whereKey($seller->id)->first()?->pivot;
        $kind = $interested ? 'interest' : 'interest_withdrawn';

        if ($interested && $assignment?->interested_at) {
            // Already interested: only the offer changes, the date of the interest stays.
            $record->sellers()->updateExistingPivot($seller->id, ['offer_price' => $offer]);
            $kind = 'offer';
        } elseif ($interested) {
            // Sellers of a record marked for selling get a row without being assigned.
            $record->sellers()->syncWithoutDetaching([$seller->id => [
                'interested_at' => now(), 'offer_price' => $offer, 'assigned' => $assignment->assigned ?? false,
            ]]);
        } elseif ($assignment && ! $assignment->assigned) {
            $record->sellers()->detach($seller->id);
        } elseif ($assignment) {
            $record->sellers()->updateExistingPivot($seller->id, ['interested_at' => null, 'offer_price' => null]);
        }
        if ($record->owner) {
            $this->notify($record->owner, new InterestChanged($record, $seller, $kind, $interested ? $offer : null));
        }

        return back()->with('info', match ($kind) {
            'offer' => __('Dein Preisvorschlag wurde gespeichert.'),
            'interest' => __('Dein Interesse wurde angemeldet.'),
            default => __('Dein Interesse wurde zurückgenommen.'),
        });
    }

    public function sell(Request $request, Record $record): RedirectResponse
    {
        Gate::authorize('update', $record);
        abort_if($record->sold, 422, __('Die Platte ist bereits verkauft.'));

        $soldPrice = $this->price($request, 'sold_price', __('Verkaufspreis'));
        $data = $request->validate([
            'seller_id' => ['required', 'integer'],
            'sold_on' => ['required', 'date'],
        ], [], ['sold_on' => __('Verkaufsdatum')]);

        $interested = $record->sellers()->wherePivotNotNull('interested_at')->get();
        $buyer = $interested->firstWhere('id', (int) $data['seller_id']);
        abort_if($buyer === null, 422, __('Dieser Verkäufer hat kein Interesse angemeldet.'));

        DB::transaction(function () use ($record, $data, $buyer, $soldPrice) {
            $record->sold = true;
            $record->selling = false;
            $record->sold_on = $data['sold_on'];
            $record->sold_price = $soldPrice;
            $record->sold_to = $buyer->name;
            $record->sold_to_user_id = $buyer->id;
            $record->save();
        });

        $mailFailed = false;
        foreach ($interested as $seller) {
            $mailFailed = ! $this->notify($seller, new RecordSold($record, $seller->is($buyer))) || $mailFailed;
        }

        $redirect = $request->input('from') === 'seller' ? redirect()->route('sellers.show', $buyer) : redirect()->route('records.show', $record);

        return $redirect
            ->with('info', __('Die Platte wurde an :name verkauft. Alle Interessenten wurden benachrichtigt.', ['name' => $buyer->name]))
            ->with('warning', $mailFailed ? __('Die Benachrichtigung steht in der App, die E-Mail konnte aber nicht verschickt werden.') : null);
    }

    /**
     * An optional price from the form; a decimal comma is accepted. Empty input gives null.
     */
    private function price(Request $request, string $field, string $label): ?string
    {
        if (is_string($request->input($field))) {
            $request->merge([$field => str_replace(',', '.', trim($request->input($field)))]);
        }
        $value = $request->validate([$field => ['nullable', 'numeric', 'min:0', 'max:99999999']], [], [$field => $label])[$field] ?? null;

        return filled($value) ? (string) $value : null;
    }

    /**
     * The notification is stored in the app first; a failing mail server must not stop the sale.
     */
    private function notify(User $user, Notification $notification): bool
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

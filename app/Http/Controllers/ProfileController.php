<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Record;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The own profile: basic information, name, e-mail and password.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user,
            'recordCount' => $user->records()->count(),
            'assignedCount' => $user->assignedRecords()->count(),
            'boughtCount' => $user->isSeller() ? Record::where('sold_to_user_id', $user->id)->count() : 0,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->email = strtolower($request->validated('email'));
        $user->save();

        return redirect()->route('profile.show')->with('info', __('Dein Profil wurde gespeichert.'));
    }
}

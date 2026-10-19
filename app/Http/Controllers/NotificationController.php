<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(20);

        // Shown once as new, then they count as read.
        $view = view('notifications.index', [
            'notifications' => $notifications,
            'unreadIds' => $notifications->getCollection()->whereNull('read_at')->pluck('id')->all(),
        ]);
        $user->unreadNotifications()->whereKey($notifications->pluck('id'))->update(['read_at' => now()]);

        return $view;
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}

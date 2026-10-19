<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // The answer is the same whether the address is known or not (also when the mail fails),
        // so the form does not reveal which e-mail addresses have an account.
        try {
            Password::sendResetLink(['email' => Str::lower(trim($request->input('email')))]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', __('Wenn ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Link verschickt.'));
    }
}

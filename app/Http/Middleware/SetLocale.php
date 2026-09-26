<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chooses German or English: the language picked in the navigation (stored in the session)
 * wins, otherwise the preferred language of the browser. German is the default.
 */
class SetLocale
{
    public const LOCALES = ['de', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        // Runs globally (also for 404 pages without session) and again in the web group with session.
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::LOCALES) ?? config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set the application locale from the user's profile, session or cookie.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the first supported locale from the current request.
     */
    private function resolveLocale(Request $request): string
    {
        $available = array_keys((array) config('app.available_locales', ['en' => 'English']));

        $candidates = [
            $request->user()?->getAttribute('locale'),
            $request->session()->get('locale'),
            $request->cookie('locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        $default = (string) config('app.locale', 'en');

        return in_array($default, $available, true) ? $default : $available[0];
    }
}

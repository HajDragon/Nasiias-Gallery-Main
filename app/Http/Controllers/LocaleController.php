<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the interface language for the current visitor.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(
            array_key_exists($locale, (array) config('app.available_locales')),
            404,
        );

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        $request->session()->put('locale', $locale);

        app()->setLocale($locale);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Rules\ValidArtworkImage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        $request->user()->notify_on_new_artwork = $request->boolean('notify_on_new_artwork');

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $locale = $request->user()->getAttribute('locale');

        if (is_string($locale)) {
            $request->session()->put('locale', $locale);
            app()->setLocale($locale);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.profile_updated')]);

        $response = to_route('profile.edit');

        return is_string($locale)
            ? $response->withCookie(cookie()->forever('locale', $locale))
            : $response;
    }

    /**
     * Upload a new profile photo for the user.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                new ValidArtworkImage,
                'mimes:jpg,jpeg,png,webp',
            ],
        ]);

        $request->user()->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.avatar_updated')]);

        return back();
    }

    /**
     * Remove the user's profile photo.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->clearMediaCollection('avatar');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.avatar_removed')]);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

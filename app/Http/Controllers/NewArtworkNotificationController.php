<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewArtworkNotificationController extends Controller
{
    /**
     * Opt the authenticated user in or out of new-artwork email notifications.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscribed' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'notify_on_new_artwork' => $validated['subscribed'],
        ]);

        return response()->json([
            'subscribed' => $request->user()->notify_on_new_artwork,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Models\ArtworkView;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RecordArtworkViewAction
{
    /**
     * Record a view and update the artwork's view count.
     */
    public function __invoke(Artwork $artwork, ?User $user = null): ?ArtworkView
    {
        $request = request();
        $visitor = $user !== null
            ? 'user:'.$user->id
            : 'guest:'.hash('sha256', $request->hasSession() ? $request->session()->getId() : (string) $request->ip());
        $key = 'artwork-view:'.$artwork->id.':'.$visitor;

        if (! Cache::add($key, true, 60)) {
            return null;
        }

        try {
            return $artwork->getConnection()->transaction(function () use ($artwork, $user): ArtworkView {
                $view = $artwork->artworkViews()->create([
                    'user_id' => $user?->id,
                    'viewed_at' => now(),
                ]);

                $artwork->increment('view_count');

                return $view;
            });
        } catch (Throwable $exception) {
            Cache::forget($key);

            throw $exception;
        }
    }
}

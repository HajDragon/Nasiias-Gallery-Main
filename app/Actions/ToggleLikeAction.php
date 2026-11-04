<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Models\User;

class ToggleLikeAction
{
    /**
     * Toggle the user's like and return whether the artwork is now liked.
     */
    public function __invoke(User $user, Artwork $artwork): bool
    {
        return $artwork->getConnection()->transaction(function () use ($user, $artwork): bool {
            $artwork->newQuery()->whereKey($artwork->getKey())->lockForUpdate()->firstOrFail();
            $artwork->refresh();

            $like = $artwork->likes()->where('user_id', $user->id)->first();

            if ($like !== null) {
                $like->delete();
                $artwork->decrement('like_count');

                return false;
            }

            $artwork->likes()->create(['user_id' => $user->id]);
            $artwork->increment('like_count');

            return true;
        });
    }
}

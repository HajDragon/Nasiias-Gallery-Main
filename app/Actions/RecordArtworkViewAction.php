<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Models\ArtworkView;
use App\Models\User;

class RecordArtworkViewAction
{
    /**
     * Record a view and update the artwork's view count.
     */
    public function execute(Artwork $artwork, ?User $user = null): ArtworkView
    {
        return $artwork->getConnection()->transaction(function () use ($artwork, $user): ArtworkView {
            $view = $artwork->artworkViews()->create([
                'user_id' => $user?->id,
                'viewed_at' => now(),
            ]);

            $artwork->increment('view_count');

            return $view;
        });
    }

    /**
     * Handle the action.
     */
    public function handle(Artwork $artwork, ?User $user = null): ArtworkView
    {
        return $this->execute($artwork, $user);
    }

    /**
     * Invoke the action as an invokable class.
     */
    public function __invoke(Artwork $artwork, ?User $user = null): ArtworkView
    {
        return $this->execute($artwork, $user);
    }
}

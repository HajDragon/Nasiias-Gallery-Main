<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Models\Comment;
use App\Models\User;

class StoreCommentAction
{
    /**
     * Store a comment on the artwork for the given user.
     */
    public function execute(User $user, Artwork $artwork, string $body): Comment
    {
        return $artwork->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }

    /**
     * Handle the action.
     */
    public function handle(User $user, Artwork $artwork, string $body): Comment
    {
        return $this->execute($user, $artwork, $body);
    }

    /**
     * Invoke the action as an invokable class.
     */
    public function __invoke(User $user, Artwork $artwork, string $body): Comment
    {
        return $this->execute($user, $artwork, $body);
    }
}

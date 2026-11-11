<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\NewArtworkPublished;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifySubscribersOfNewArtwork implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Artwork $artwork) {}

    /**
     * Send the new-artwork email to every user who opted in.
     */
    public function handle(): void
    {
        User::query()
            ->where('notify_on_new_artwork', true)
            ->lazyById()
            ->each(function (User $user): void {
                Mail::to($user)->send(new NewArtworkPublished($this->artwork));
            });
    }
}

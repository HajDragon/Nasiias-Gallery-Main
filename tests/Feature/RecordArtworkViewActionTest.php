<?php

namespace Tests\Feature;

use App\Actions\RecordArtworkViewAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordArtworkViewActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_guest_view_and_increments_the_count(): void
    {
        $this->freezeTime();
        $artwork = Artwork::factory()->create(['view_count' => 3]);

        $view = (new RecordArtworkViewAction)->execute($artwork);

        $this->assertDatabaseHas('artwork_views', [
            'id' => $view->id,
            'artwork_id' => $artwork->id,
            'user_id' => null,
            'viewed_at' => now()->toDateTimeString(),
        ]);
        $this->assertSame(4, $artwork->fresh()->view_count);
    }

    public function test_records_a_view_for_the_given_user(): void
    {
        $artwork = Artwork::factory()->create();
        $user = User::factory()->create();

        $view = (new RecordArtworkViewAction)->handle($artwork, $user);

        $this->assertDatabaseHas('artwork_views', [
            'id' => $view->id,
            'artwork_id' => $artwork->id,
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, $artwork->fresh()->view_count);
    }

    public function test_repeated_views_are_recorded_individually(): void
    {
        $artwork = Artwork::factory()->create();
        $user = User::factory()->create();
        $action = new RecordArtworkViewAction;

        $action($artwork, $user);
        $action($artwork, $user);

        $this->assertSame(2, $artwork->artworkViews()->count());
        $this->assertSame(2, $artwork->fresh()->view_count);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\RecordArtworkViewAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecordArtworkViewActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_records_a_guest_view_and_increments_the_count(): void
    {
        $this->freezeTime();
        $artwork = Artwork::factory()->create(['view_count' => 3]);

        $view = (new RecordArtworkViewAction)($artwork);

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

        $view = (new RecordArtworkViewAction)($artwork, $user);

        $this->assertDatabaseHas('artwork_views', [
            'id' => $view->id,
            'artwork_id' => $artwork->id,
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, $artwork->fresh()->view_count);
    }

    public function test_repeated_views_are_skipped_until_the_cooldown_expires(): void
    {
        $this->freezeTime();
        $artwork = Artwork::factory()->create();
        $user = User::factory()->create();
        $action = new RecordArtworkViewAction;

        $action($artwork, $user);
        $this->assertNull($action($artwork, $user));
        $this->assertSame(1, $artwork->artworkViews()->count());
        $this->assertSame(1, $artwork->fresh()->view_count);

        $this->travel(61)->seconds();
        $this->assertNotNull($action($artwork, $user));

        $this->assertSame(2, $artwork->artworkViews()->count());
        $this->assertSame(2, $artwork->fresh()->view_count);
    }

    public function test_guest_refreshes_are_skipped(): void
    {
        $artwork = Artwork::factory()->create();
        $action = new RecordArtworkViewAction;

        $this->assertNotNull($action($artwork));
        $this->assertNull($action($artwork));
        $this->assertSame(1, $artwork->fresh()->view_count);
    }

    public function test_cooldown_is_separate_for_each_user_and_artwork(): void
    {
        $artwork = Artwork::factory()->create();
        $otherArtwork = Artwork::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $action = new RecordArtworkViewAction;

        $this->assertNotNull($action($artwork, $user));
        $this->assertNotNull($action($artwork, $otherUser));
        $this->assertNotNull($action($otherArtwork, $user));
        $this->assertSame(2, $artwork->fresh()->view_count);
        $this->assertSame(1, $otherArtwork->fresh()->view_count);
    }
}

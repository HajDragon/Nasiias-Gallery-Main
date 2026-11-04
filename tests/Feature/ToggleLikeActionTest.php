<?php

namespace Tests\Feature;

use App\Actions\ToggleLikeAction;
use App\Models\Artwork;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleLikeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_like_and_increments_the_count(): void
    {
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        $liked = (new ToggleLikeAction)->execute($user, $artwork);

        $this->assertTrue($liked);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
        ]);
        $this->assertSame(1, $artwork->fresh()->like_count);
    }

    public function test_removes_only_the_given_users_like_and_decrements_the_count(): void
    {
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create(['like_count' => 2]);
        Like::factory()->create(['user_id' => $user->id, 'artwork_id' => $artwork->id]);
        $otherLike = Like::factory()->create(['artwork_id' => $artwork->id]);

        $liked = (new ToggleLikeAction)->handle($user, $artwork);

        $this->assertFalse($liked);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
        ]);
        $this->assertDatabaseHas('likes', ['id' => $otherLike->id]);
        $this->assertSame(1, $artwork->fresh()->like_count);
    }

    public function test_repeated_calls_toggle_the_like(): void
    {
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $action = new ToggleLikeAction;

        $this->assertTrue($action($user, $artwork));
        $this->assertFalse($action($user, $artwork));
        $this->assertTrue($action($user, $artwork));

        $this->assertSame(1, $artwork->likes()->count());
        $this->assertSame(1, $artwork->fresh()->like_count);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\RecordArtworkViewAction;
use App\Actions\ToggleLikeAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class ArtworkInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_requests_use_the_action_and_deduplicate_refreshes(): void
    {
        // Arrange: require the actual view action to run through the route.
        Cache::flush();
        $artwork = Artwork::factory()->create();
        $this->partialMock(RecordArtworkViewAction::class, fn (MockInterface $mock) => $mock->shouldReceive('__invoke')->twice()->passthru());

        // Act: refresh as the same visitor, then allow the cooldown to expire.
        $first = $this->get(route('gallery.show', $artwork))->assertOk();
        $sessionId = $first->getCookie(config('session.cookie'))->getValue();
        $this->withCookie(config('session.cookie'), $sessionId)->get(route('gallery.show', $artwork))->assertOk();

        // Assert: refreshes produce a single view record and counter update.
        $this->assertSame(1, $artwork->fresh()->view_count);
        $this->assertDatabaseCount('artwork_views', 1);
    }

    public function test_json_requests_return_the_artwork_detail_for_the_overlay_modal(): void
    {
        // Arrange: an artwork the overlay modal will open without navigating.
        $artwork = Artwork::factory()->create();

        // Act: request the detail as plain JSON.
        $response = $this->getJson(route('gallery.show', $artwork));

        // Assert: the same detail payload the standalone page receives.
        $response->assertOk()->assertJsonPath('id', $artwork->id)->assertJsonStructure(['title', 'alt_text', 'image_url', 'like_count']);
    }

    public function test_prefetch_and_head_requests_do_not_count_as_views(): void
    {
        // Arrange: speculative requests should not update popularity.
        $artwork = Artwork::factory()->create();

        // Act: issue each browser prefetch form and an HTTP HEAD request.
        $this->get(route('gallery.show', $artwork), ['Purpose' => 'prefetch'])->assertOk();
        $this->get(route('gallery.show', $artwork), ['Sec-Purpose' => 'prefetch'])->assertOk();
        $this->head(route('gallery.show', $artwork))->assertOk();

        // Assert: no records or counter increments are performed.
        $this->assertDatabaseCount('artwork_views', 0);
        $this->assertSame(0, $artwork->fresh()->view_count);
    }

    public function test_like_route_uses_the_transactional_action_and_returns_current_count(): void
    {
        // Arrange: bypassing the shared action must fail this regression test.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $this->partialMock(ToggleLikeAction::class, fn (MockInterface $mock) => $mock->shouldReceive('__invoke')->twice()->passthru());

        // Act and assert: both toggles update the same persisted counter.
        $this->actingAs($user)->postJson(route('gallery.like', $artwork))->assertOk()->assertJson(['liked' => true, 'like_count' => 1]);
        $this->postJson(route('gallery.like', $artwork))->assertOk()->assertJson(['liked' => false, 'like_count' => 0]);
        $this->assertSame(0, $artwork->fresh()->like_count);
    }

    public function test_comment_rate_limits_are_per_user_and_expire(): void
    {
        // Arrange: two users share an IP; one should not exhaust the other's budget.
        Cache::flush();
        $this->freezeTime();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $artwork = Artwork::factory()->create();

        // Act: exceed the write limit for one user.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), ['body' => 'Hello'])->assertCreated();
        }

        // Assert: deny excess writes, retain other users' access, and reset after expiry.
        $this->postJson(route('gallery.comments.store', $artwork), ['body' => 'Hello'])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->actingAs($otherUser)->postJson(route('gallery.comments.store', $artwork), ['body' => 'Hello'])->assertCreated();
        $this->travel(61)->seconds();
        $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), ['body' => 'Hello'])->assertCreated();
        $this->assertDatabaseCount('comments', 12);
    }
}

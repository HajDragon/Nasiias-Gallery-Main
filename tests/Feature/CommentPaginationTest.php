<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cursors_keep_comments_bounded_and_stable_when_new_comments_arrive(): void
    {
        // Arrange: tied timestamps and another artwork must not cause overlap or leakage.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $comments = Comment::factory()->count(45)->for($artwork)->for($user)->create(['created_at' => now()]);
        Comment::factory()->create();

        // Act: read the first page, then add a new comment before reading older pages.
        $first = $this->actingAs($user)->getJson(route('gallery.comments.index', $artwork))->assertOk()->assertJsonCount(20, 'data');
        Comment::factory()->for($artwork)->for($user)->create(['created_at' => now()->addSecond()]);
        $second = $this->getJson($first->json('next_page_url'))->assertOk()->assertJsonCount(20, 'data');
        $third = $this->getJson($second->json('next_page_url'))->assertOk()->assertJsonCount(5, 'data');

        // Assert: every original comment occurs exactly once and has an author.
        $ids = array_merge($first->json('data.*.id'), $second->json('data.*.id'), $third->json('data.*.id'));
        $this->assertCount(45, array_unique($ids));
        $this->assertEqualsCanonicalizing($comments->pluck('id')->all(), $ids);
        $third->assertJsonPath('next_page_url', null);
        $first->assertJsonPath('data.0.user.display_name', $user->display_name);
    }
}

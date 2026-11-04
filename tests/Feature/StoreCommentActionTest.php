<?php

namespace Tests\Feature;

use App\Actions\StoreCommentAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCommentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_comment_for_the_given_user_and_artwork(): void
    {
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $body = "Beautiful colours!\nI love the composition.";

        $comment = (new StoreCommentAction)($user, $artwork, $body);

        $this->assertTrue($comment->exists);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
            'body' => $body,
        ]);
        $this->assertTrue($comment->user->is($user));
        $this->assertTrue($comment->artwork->is($artwork));
    }
}

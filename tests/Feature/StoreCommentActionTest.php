<?php

namespace Tests\Feature;

use App\Actions\StoreCommentAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreCommentActionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('actionMethods')]
    public function test_stores_a_comment_for_the_given_user_and_artwork(string $method): void
    {
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $body = "Beautiful colours!\nI love the composition.";

        $comment = (new StoreCommentAction)->{$method}($user, $artwork, $body);

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

    /**
     * @return array<string, array{string}>
     */
    public static function actionMethods(): array
    {
        return [
            'execute' => ['execute'],
            'handle' => ['handle'],
            'invoke' => ['__invoke'],
        ];
    }
}

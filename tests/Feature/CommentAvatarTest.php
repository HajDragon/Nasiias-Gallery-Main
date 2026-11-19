<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_includes_the_author_avatar_url(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))
            ->toMediaCollection('avatar');

        $artwork = Artwork::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('gallery.comments.index', $artwork));

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('0.id', $comment->id)
            ->assertJsonPath('0.user.display_name', $user->display_name);

        $this->assertNotNull($response->json('0.user.avatar'));
    }

    public function test_comment_avatar_is_null_without_a_profile_photo(): void
    {
        // Arrange
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('gallery.comments.index', $artwork));

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('0.user.avatar', null);
    }

    public function test_posted_comment_returns_the_author_avatar(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))
            ->toMediaCollection('avatar');

        $artwork = Artwork::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->postJson(route('gallery.comments.store', $artwork), [
                'body' => 'Beautiful work!',
            ]);

        // Assert
        $response
            ->assertCreated()
            ->assertJsonPath('user.display_name', $user->display_name);

        $this->assertNotNull($response->json('user.avatar'));
    }
}

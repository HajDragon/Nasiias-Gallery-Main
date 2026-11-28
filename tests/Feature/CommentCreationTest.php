<?php

namespace Tests\Feature;

use App\Actions\StoreCommentAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CommentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_submission_uses_the_shared_creation_action(): void
    {
        // Arrange: require the HTTP endpoint to use the real action for this user and artwork.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $body = "Beautiful colours!\nЧудова робота 🎨";
        $this->partialMock(StoreCommentAction::class, function (MockInterface $mock) use ($user, $artwork, $body): void {
            $mock->shouldReceive('__invoke')
                ->once()
                ->withArgs(fn (User $author, Artwork $target, string $text): bool => $author->is($user)
                    && $target->is($artwork) && $text === $body)
                ->passthru();
        });

        // Act: submit through the route used by the website.
        $response = $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), [
            'body' => $body,
        ]);

        // Assert: preserve the stored text, ownership, and existing response shape.
        $response->assertCreated()
            ->assertJsonPath('body', $body)
            ->assertJsonPath('user.display_name', $user->display_name)
            ->assertJsonStructure(['id', 'body', 'user' => ['display_name'], 'created_at']);
        $this->assertDatabaseCount('comments', 1);
        $this->assertDatabaseHas('comments', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
            'body' => $body,
        ]);
    }

    public function test_invalid_comments_are_rejected_before_calling_the_action(): void
    {
        // Arrange: invalid input must never reach comment creation.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $this->mock(StoreCommentAction::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('__invoke');
        });

        // Act: exercise missing, empty, non-string, and oversized bodies.
        foreach ([[], ['body' => ''], ['body' => ['invalid']], ['body' => str_repeat('a', StoreCommentAction::MAX_INPUT_LENGTH + 1)]] as $payload) {
            $response = $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), $payload);

            // Assert: retain the current validation errors and create no records.
            $response->assertUnprocessable()->assertJsonValidationErrors('body');
        }
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_submitted_markup_is_cleaned_in_both_the_database_and_response(): void
    {
        // Arrange: send markup, an executable block, and normal multilingual text.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        // Act: submit through the real HTTP endpoint.
        $response = $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), [
            'body' => "  <b>Heyy</b><script>alert(1)</script>\nЧудова робота 🎨  ",
        ]);

        // Assert: clients and persistent storage receive the same cleaned plain text.
        $response->assertCreated()->assertJsonPath('body', "Heyy\nЧудова робота 🎨");
        $this->assertDatabaseHas('comments', [
            'id' => $response->json('id'), 'body' => "Heyy\nЧудова робота 🎨",
        ]);
    }

    public function test_comments_that_are_empty_or_too_long_after_cleaning_are_rejected(): void
    {
        // Arrange: these bodies pass the raw limit but fail the cleaned-text rules.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        // Act: submit each invalid plain-text result.
        foreach (['<b> </b>', '<script>alert(1)</script>', '<img src=x onerror=alert(1)>', str_repeat('a', 281)] as $body) {
            $response = $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), ['body' => $body]);

            // Assert: cleaning cannot result in an empty or oversized database record.
            $response->assertUnprocessable()->assertJsonValidationErrors('body');
        }
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_length_is_checked_on_cleaned_text_instead_of_markup(): void
    {
        // Arrange: markup makes the request longer than 280 characters.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $text = str_repeat('я', 280);

        // Act: submit a comment exactly at the cleaned-text limit.
        $response = $this->actingAs($user)->postJson(route('gallery.comments.store', $artwork), ['body' => '<b>'.$text.'</b>']);

        // Assert: all 280 characters are preserved without truncation.
        $response->assertCreated()->assertJsonPath('body', $text);
        $this->assertDatabaseHas('comments', ['body' => $text]);
    }

    public function test_guests_cannot_create_comments(): void
    {
        // Arrange: create an artwork without authenticating a user.
        $artwork = Artwork::factory()->create();

        // Act: submit a comment as a guest.
        $response = $this->postJson(route('gallery.comments.store', $artwork), ['body' => 'Hello']);

        // Assert: authentication is still required and no comment is stored.
        $response->assertUnauthorized();
        $this->assertDatabaseCount('comments', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\StoreCommentAction;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreCommentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_comment_for_the_given_user_and_artwork(): void
    {
        // Arrange: plain text with an intentional line break must survive unchanged.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();
        $body = "Beautiful colours!\nI love the composition.";

        // Act: create a comment through the shared action.
        $comment = (new StoreCommentAction)($user, $artwork, $body);

        // Assert: preserve the original text and both relationships.
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

    /** @return array<string, array{string, string}> */
    public static function sanitizationCases(): array
    {
        return [
            'formatting' => ['  <b>Beautiful</b> <em>painting!</em>  ', 'Beautiful painting!'],
            'scripts' => ['Hello<ScRiPt>alert(1)</ScRiPt> world', 'Hello world'],
            'styles' => ['Hello<style>body { display: none; }</style> world', 'Hello world'],
            'event handlers' => ['<img src=x onerror=alert(1)>Nice', 'Nice'],
            'links' => ['<a href="javascript:alert(1)" onclick="alert(1)">Nice</a>', 'Nice'],
            'embedded content' => ['Hello<iframe>unwanted</iframe><object>unwanted</object> world', 'Hello world'],
            'malformed markup' => ['<b>Nice <i>work', 'Nice work'],
            'comments' => ['Nice<!-- hidden --> work', 'Nice work'],
            'multilingual text' => ["  Привіт 😁\r\nГарна робота!  ", "Привіт 😁\nГарна робота!"],
            'punctuation and entities' => ['Tom &amp; Jerry &lt;3 &quot;art&quot;', 'Tom & Jerry <3 "art"'],
            'unicode whitespace' => ["\u{00A0}<b>Nice</b>\u{00A0}", 'Nice'],
            'tabs and line breaks' => ["Hello\tworld\n\nNice!", "Hello\tworld\n\nNice!"],
            'control characters' => ["Hello\0\x01 world", 'Hello world'],
            'angle brackets in plain text' => ['2 < 3 & 5 > 4', '2 < 3 & 5 > 4'],
            'foreign markup' => ['Nice<svg><script>alert(1)</script></svg><math><mi>x</mi></math> work', 'Nice work'],
        ];
    }

    #[DataProvider('sanitizationCases')]
    public function test_direct_action_calls_store_cleaned_plain_text(string $input, string $expected): void
    {
        // Arrange: direct action callers must get the same cleaning as HTTP callers.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        // Act: create a comment from the supplied markup or plain text.
        $comment = (new StoreCommentAction)($user, $artwork, $input);

        // Assert: inspect the persisted value rather than only the in-memory model.
        $this->assertSame($expected, $comment->fresh()->body);
    }

    public function test_direct_action_calls_cannot_store_empty_or_oversized_comments(): void
    {
        // Arrange: each input must be rejected before a record is created.
        $user = User::factory()->create();
        $artwork = Artwork::factory()->create();

        // Act: call the action without going through request validation.
        foreach (['<script>alert(1)</script>', '<b> </b>', str_repeat('a', 281), str_repeat('a', StoreCommentAction::MAX_INPUT_LENGTH + 1)] as $body) {
            try {
                (new StoreCommentAction)($user, $artwork, $body);
                $this->fail('Invalid comment was accepted.');
            } catch (ValidationException $exception) {
                // Assert: the error belongs to the comment field.
                $this->assertArrayHasKey('body', $exception->errors());
            }
        }
        $this->assertDatabaseCount('comments', 0);
    }
}

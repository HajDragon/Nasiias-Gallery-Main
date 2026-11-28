<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class StoreCommentAction
{
    public const int MAX_INPUT_LENGTH = 10_000;

    /**
     * Store a comment on the artwork for the given user.
     */
    public function __invoke(User $user, Artwork $artwork, string $body): Comment
    {
        Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:'.self::MAX_INPUT_LENGTH],
        ])->validate();

        $body = $this->sanitize($body);

        Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:280'],
        ])->validate();

        return $artwork->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }

    private function sanitize(string $body): string
    {
        // Remove control characters while preserving tabs and line breaks.
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';

        // Unwrap safe HTML elements into text. Unsafe elements and their contents
        // retain the sanitizer's default drop policy, including scripts and styles.
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->withMaxInputLength(4 * self::MAX_INPUT_LENGTH);

        foreach (array_keys($config->getAllowedElements()) as $element) {
            $config = $config->blockElement($element);
        }

        $text = (new HtmlSanitizer($config))->sanitize($body);

        // Store plain text rather than HTML entities; rendering must still escape it.
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::trim($text);
    }
}

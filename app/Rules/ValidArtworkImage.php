<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Server-side security validation for artwork image uploads.
 *
 * The client-supplied filename, extension, and MIME type are never trusted.
 * Instead the file contents are sniffed with finfo and structurally verified
 * with getimagesize(), which rejects disguised scripts, forged magic bytes,
 * truncated files, polyglot payloads, and decompression bombs.
 *
 * All failure messages are translation keys under `validation.artwork_image`
 * so wording and locale can change without touching this class.
 */
class ValidArtworkImage implements ValidationRule
{
    /**
     * Maximum allowed upload size in kilobytes (10 MB).
     */
    public const MAX_SIZE_KB = 10240;

    /**
     * Maximum allowed width or height in pixels. Guards against "image
     * bombs": tiny files whose headers declare massive dimensions and are
     * expanded into RAM when decoded or resized.
     */
    public const MAX_DIMENSION_PX = 8000;

    /**
     * File extensions an artwork upload may carry.
     *
     * @var list<string>
     */
    public const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Image MIME types detected from the file contents, mapped to the
     * extensions each format is allowed to use.
     *
     * @var array<string, list<string>>
     */
    public const ACCEPTED_MIME_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
    ];

    /**
     * GD image type identifiers mapped back to their MIME types.
     *
     * @var array<int, string>
     */
    private const IMAGE_TYPES = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
        IMAGETYPE_GIF => 'image/gif',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('validation.artwork_image.not_a_file')->translate();

            return;
        }

        if (! $value->isValid()) {
            $fail('validation.artwork_image.upload_failed')->translate();

            return;
        }

        if ($value->getSize() > self::MAX_SIZE_KB * 1024) {
            $fail('validation.artwork_image.too_large')->translate([
                'max' => self::MAX_SIZE_KB / 1024,
            ]);

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::ACCEPTED_EXTENSIONS, true)) {
            $fail('validation.artwork_image.bad_extension')->translate([
                'extensions' => implode(', .', self::ACCEPTED_EXTENSIONS),
            ]);

            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            $fail('validation.artwork_image.unreadable')->translate();

            return;
        }

        // Sniff the real type from the file contents; neither the client
        // extension nor the client MIME type is trusted.
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (! isset(self::ACCEPTED_MIME_TYPES[$mimeType])) {
            $fail('validation.artwork_image.not_image')->translate();

            return;
        }

        if (! in_array($extension, self::ACCEPTED_MIME_TYPES[$mimeType], true)) {
            $fail('validation.artwork_image.extension_mismatch')->translate([
                'extension' => $extension,
                'mime' => $mimeType,
            ]);

            return;
        }

        // Verify the file is a structurally valid, decodable image. This
        // rejects files that only fake the leading magic bytes.
        $info = @getimagesize($path);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            $fail('validation.artwork_image.corrupt')->translate();

            return;
        }

        if ($info[0] > self::MAX_DIMENSION_PX || $info[1] > self::MAX_DIMENSION_PX) {
            $fail('validation.artwork_image.dimensions_exceeded')->translate([
                'max_dimension' => self::MAX_DIMENSION_PX,
            ]);

            return;
        }

        if ((self::IMAGE_TYPES[$info[2]] ?? null) !== $mimeType) {
            $fail('validation.artwork_image.type_mismatch')->translate();
        }
    }
}

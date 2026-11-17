<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Validates the value produced by the Filament photo editor field.
 *
 * The editor never uploads a file: it writes the edited bitmap to the form
 * state as a base64 data URI. Untouched images on the edit screen are stored
 * as a disk-relative path instead. Both are user-controlled strings, so the
 * same structural checks as ValidArtworkImage are applied here before the
 * value is written to the media library:
 *
 *  - size limit, path traversal and non-string payloads are rejected,
 *  - the real MIME type is sniffed from the bytes with finfo,
 *  - getimagesize() proves the file is a decodable image and exposes its
 *    declared dimensions, which guards against image bombs.
 */
final class ValidArtworkImagePayload implements ValidationRule
{
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

    public function __construct(
        private readonly string $disk = 'public',
        private readonly ?string $unchangedUrl = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Livewire hands temporary uploads to the field as file objects, for
        // example when the form is filled programmatically.
        if ($value instanceof UploadedFile) {
            (new ValidArtworkImage)->validate($attribute, $value, $fail);

            return;
        }

        if (! is_string($value)) {
            $fail('validation.artwork_image.not_a_file')->translate();

            return;
        }

        if (str_starts_with($value, 'data:image/')) {
            $this->validateDataUri($value, $fail);

            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            // Some artworks point at an image hosted elsewhere (for example a
            // hot-linked demo photo). The editor shows it as-is, so leaving it
            // untouched must not block the save: nothing is downloaded or
            // copied into the media library for this value.
            if ($this->unchangedUrl !== null && $value === $this->unchangedUrl) {
                return;
            }

            $fail('validation.artwork_image.not_a_file')->translate();

            return;
        }

        $this->validateStoredPath($value, $fail);
    }

    /**
     * Validate an image the editor exported as a base64 data URI.
     */
    private function validateDataUri(string $value, Closure $fail): void
    {
        // Refuse oversized payloads before decoding them into memory.
        if (strlen($value) > (int) ceil(ValidArtworkImage::MAX_SIZE_KB * 1024 * 4 / 3) + 128) {
            $fail('validation.artwork_image.too_large')->translate([
                'max' => ValidArtworkImage::MAX_SIZE_KB / 1024,
            ]);

            return;
        }

        $encoded = substr($value, (int) strpos($value, ',') + 1);
        $bytes = base64_decode($encoded, true);

        if ($bytes === false || $bytes === '') {
            $fail('validation.artwork_image.corrupt')->translate();

            return;
        }

        if (strlen($bytes) > ValidArtworkImage::MAX_SIZE_KB * 1024) {
            $fail('validation.artwork_image.too_large')->translate([
                'max' => ValidArtworkImage::MAX_SIZE_KB / 1024,
            ]);

            return;
        }

        $this->validateContents($bytes, $fail);
    }

    /**
     * Validate an image that already lives on the configured disk.
     */
    private function validateStoredPath(string $path, Closure $fail): void
    {
        // Reject remote wrappers, absolute paths and traversal outright: the
        // editor only ever submits a path relative to the public disk.
        if (
            preg_match('#^[A-Za-z0-9][A-Za-z0-9._/-]*$#', $path) !== 1
            || str_contains($path, '..')
            || str_contains($path, '\\')
        ) {
            $fail('validation.artwork_image.not_a_file')->translate();

            return;
        }

        $disk = Storage::disk($this->disk);

        if (! $disk->exists($path)) {
            $fail('validation.artwork_image.unreadable')->translate();

            return;
        }

        if ($disk->size($path) > ValidArtworkImage::MAX_SIZE_KB * 1024) {
            $fail('validation.artwork_image.too_large')->translate([
                'max' => ValidArtworkImage::MAX_SIZE_KB / 1024,
            ]);

            return;
        }

        $this->validateContents((string) $disk->get($path), $fail);
    }

    /**
     * Sniff and structurally verify an in-memory image.
     */
    private function validateContents(string $bytes, Closure $fail): void
    {
        $path = tempnam(sys_get_temp_dir(), 'artwork-image-');

        if ($path === false) {
            $fail('validation.artwork_image.unreadable')->translate();

            return;
        }

        try {
            file_put_contents($path, $bytes);

            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);

            if (! isset(ValidArtworkImage::ACCEPTED_MIME_TYPES[$mimeType])) {
                $fail('validation.artwork_image.not_image')->translate();

                return;
            }

            $info = @getimagesize($path);

            if ($info === false || $info[0] < 1 || $info[1] < 1) {
                $fail('validation.artwork_image.corrupt')->translate();

                return;
            }

            if ($info[0] > ValidArtworkImage::MAX_DIMENSION_PX || $info[1] > ValidArtworkImage::MAX_DIMENSION_PX) {
                $fail('validation.artwork_image.dimensions_exceeded')->translate([
                    'max_dimension' => ValidArtworkImage::MAX_DIMENSION_PX,
                ]);

                return;
            }

            if ((self::IMAGE_TYPES[$info[2]] ?? null) !== $mimeType) {
                $fail('validation.artwork_image.type_mismatch')->translate();
            }
        } finally {
            try {
                unlink($path);
            } catch (Throwable) {
                // The temporary file is already gone; nothing to clean up.
            }
        }
    }
}

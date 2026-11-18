<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * The admin photo editor does not upload a file: it exports the edited
 * bitmap as a base64 data URI, in the configured output format (WebP).
 */
trait BuildsEditedPhotoPayloads
{
    protected function editedPhotoBytes(string $format = 'webp', int $width = 1200, int $height = 800): string
    {
        return (string) UploadedFile::fake()->image("photo.{$format}", $width, $height)->getContent();
    }

    protected function editedPhotoPayload(string $format = 'webp', int $width = 1200, int $height = 800): string
    {
        return "data:image/{$format};base64,".base64_encode($this->editedPhotoBytes($format, $width, $height));
    }
}

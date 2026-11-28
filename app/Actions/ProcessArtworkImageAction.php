<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Rules\ValidArtworkImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProcessArtworkImageAction
{
    /**
     * Process and attach an uploaded image to the artwork.
     */
    public function execute(Artwork $artwork, UploadedFile $file, string $collection = 'images'): Media
    {
        // Defense in depth: never attach a file that fails the security
        // checks, even when this action is called outside the admin form.
        Validator::make(
            ['image' => $file],
            ['image' => [new ValidArtworkImage]],
        )->validate();

        if (! $artwork->exists) {
            $artwork->save();
        }

        $dimensions = getimagesize($file->getPathname());
        if ($dimensions === false) {
            throw ValidationException::withMessages(['image' => __('validation.image', ['attribute' => 'image'])]);
        }
        $media = $artwork->addMedia($file)
            ->withCustomProperties(['width' => $dimensions[0], 'height' => $dimensions[1]])
            ->toMediaCollection($collection);

        $artwork->forceFill([
            'image_url' => $media->getUrl(),
        ])->save();

        return $media;
    }

    /**
     * Handle the action.
     */
    public function handle(Artwork $artwork, UploadedFile $file, string $collection = 'images'): Media
    {
        return $this->execute($artwork, $file, $collection);
    }

    /**
     * Invoke the action as an invokable class.
     */
    public function __invoke(Artwork $artwork, UploadedFile $file, string $collection = 'images'): Media
    {
        return $this->execute($artwork, $file, $collection);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Artwork;
use App\Rules\ValidArtworkImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
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

        $media = $artwork->addMedia($file)
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

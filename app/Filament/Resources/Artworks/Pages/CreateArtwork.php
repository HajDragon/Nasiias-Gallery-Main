<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Actions\ProcessArtworkImageAction;
use App\Filament\Resources\Artworks\ArtworkResource;
use App\Models\Artwork;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateArtwork extends CreateRecord
{
    protected static string $resource = ArtworkResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $filePath = $data['file'] ?? null;
        unset($data['file']);

        if (empty($data['alt_text']) && ! empty($data['title'])) {
            $data['alt_text'] = (string) $data['title'];
        } elseif (empty($data['alt_text'])) {
            $data['alt_text'] = 'Artwork image';
        }

        $data['image_url'] ??= '';

        /** @var Artwork $artwork */
        $artwork = static::getModel()::create($data);

        if (is_string($filePath) && $filePath !== '') {
            $disk = Storage::disk('public');
            $fullPath = $disk->path($filePath);

            if (file_exists($fullPath)) {
                $uploadedFile = new UploadedFile(
                    $fullPath,
                    basename($fullPath),
                    mime_content_type($fullPath) ?: null,
                    null,
                    true
                );

                app(ProcessArtworkImageAction::class)->execute($artwork, $uploadedFile);
            }
        }

        return $artwork;
    }
}

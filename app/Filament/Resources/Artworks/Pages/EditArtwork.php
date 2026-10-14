<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Actions\ProcessArtworkImageAction;
use App\Filament\Resources\Artworks\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $filePath = $data['file'] ?? null;
        unset($data['file']);

        if (empty($data['alt_text']) && ! empty($data['title'])) {
            $data['alt_text'] = (string) $data['title'];
        }

        $record->update($data);

        /** @var Artwork $record */
        if (is_string($filePath) && $filePath !== '' && $filePath !== $record->image_url) {
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

                app(ProcessArtworkImageAction::class)->execute($record, $uploadedFile);
            }
        }

        return $record;
    }
}

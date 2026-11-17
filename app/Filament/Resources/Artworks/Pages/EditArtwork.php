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
     * Show the published photo inside the editor so it can be adjusted in
     * place instead of being uploaded again.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Artwork $record */
        $record = $this->getRecord();

        $data['file'] = $this->currentPhotoValue($record);

        return $data;
    }

    /**
     * Resolve the value the editor has to open with.
     *
     * Media library files are addressed by their path on the public disk so
     * that saving an untouched photo stays a no-op. Artworks created before
     * the media library (or seeded with a hot-linked photo) only have an
     * `image_url`, which is resolved to a disk path when it points at our own
     * storage and otherwise shown as the remote URL it is.
     */
    private function currentPhotoValue(Artwork $record): ?string
    {
        $media = $record->getFirstMedia('images');

        if ($media !== null) {
            return $media->getPathRelativeToRoot();
        }

        $url = trim((string) $record->image_url);

        if ($url === '') {
            return null;
        }

        $prefix = rtrim((string) parse_url(Storage::disk('public')->url('/'), PHP_URL_PATH), '/').'/';
        $path = (string) parse_url($url, PHP_URL_PATH);
        $relative = substr($path, strlen($prefix));

        if (
            str_starts_with($path, $prefix)
            && $relative !== ''
            && Storage::disk('public')->exists($relative)
        ) {
            return $relative;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $photo = $data['file'] ?? null;
        unset($data['file']);

        if (empty($data['alt_text']) && ! empty($data['title'])) {
            $data['alt_text'] = (string) $data['title'];
        }

        $record->update($data);

        /** @var Artwork $record */
        $media = $record->getFirstMedia('images');

        // Pressing "Remove" in the editor clears the field, so an empty value
        // means the published photo has to disappear with the next save.
        if (blank($photo)) {
            if ($media !== null) {
                $record->clearMediaCollection('images');
            }

            if (filled($record->image_url)) {
                $record->forceFill(['image_url' => ''])->save();
            }

            return $record;
        }

        // Things that are not a local file: the untouched photo the form was
        // filled with, or a remote URL the editor displayed as-is.
        if (
            ! is_string($photo)
            || str_contains($photo, '://')
            || $photo === $this->currentPhotoValue($record)
        ) {
            return $record;
        }

        $fullPath = Storage::disk('public')->path($photo);

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

        return $record;
    }
}

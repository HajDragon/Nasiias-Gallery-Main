<?php

namespace App\Http\Resources;

use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtworkCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Artwork $artwork */
        $artwork = $this->resource;

        return [
            'id' => $artwork->id,
            'title' => $artwork->title,
            'alt_text' => $artwork->alt_text,
            ...$artwork->imageSources(),
            'category' => $artwork->relationLoaded('category') && $artwork->category !== null
                ? ['name' => $artwork->category->name, 'slug' => $artwork->category->slug]
                : null,
        ];
    }
}

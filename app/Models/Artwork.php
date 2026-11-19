<?php

declare(strict_types=1);

namespace App\Models;

use App\Rules\ValidArtworkImage;
use Database\Factories\ArtworkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $category_id
 * @property string|null $title
 * @property string|null $description
 * @property string $image_url
 * @property string $alt_text
 * @property int $view_count
 * @property int $like_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['category_id', 'title', 'description', 'image_url', 'alt_text', 'view_count', 'like_count'])]
class Artwork extends Model implements HasMedia
{
    /** @use HasFactory<ArtworkFactory> */
    use HasFactory, HasSEO, InteractsWithMedia;

    public function getDynamicSEOData(): SEOData
    {
        $seoData = new SEOData();
        $seoData->title = $this->title;
        $seoData->description = $this->description;
        $seoData->image = $this->getFirstMediaUrl('images') ?: $this->image_url;
        $seoData->type = 'article';
        $seoData->published_time = $this->created_at;
        $seoData->modified_time = $this->updated_at;
        $seoData->section = $this->category?->name;
        $seoData->tags = [$this->category?->name];
        return $seoData;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->singleFile()
            ->acceptsMimeTypes(array_keys(ValidArtworkImage::ACCEPTED_MIME_TYPES));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('compressed')
            ->nonQueued()
            ->quality(80);

        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->fit(Fit::Crop, 300, 300)
            ->quality(80);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<ArtworkView, $this>
     */
    public function artworkViews(): HasMany
    {
        return $this->hasMany(ArtworkView::class);
    }
}

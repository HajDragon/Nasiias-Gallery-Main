<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Database\Factories\ArtworkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $image_url
 * @property string $alt_text
 * @property int $view_count
 * @property int $like_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['category_id', 'image_url', 'alt_text', 'view_count', 'like_count'])]
class Artwork extends Model
{
    /** @use HasFactory<ArtworkFactory> */
    use HasFactory;

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

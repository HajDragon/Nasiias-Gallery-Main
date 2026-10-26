<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ArtworkViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $artwork_id
 * @property int|null $user_id
 * @property Carbon $viewed_at
 */
#[Fillable(['artwork_id', 'user_id', 'viewed_at'])]
class ArtworkView extends Model
{
    /** @use HasFactory<ArtworkViewFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<Artwork, $this>
     */
    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

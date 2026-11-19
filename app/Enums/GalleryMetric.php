<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Artwork;
use App\Models\ArtworkView;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Like;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

enum GalleryMetric: string
{
    case Artworks = 'artworks';
    case Categories = 'categories';
    case Likes = 'likes';
    case Views = 'views';
    case Comments = 'comments';
    case Users = 'users';
    case Messages = 'messages';

    public function label(): string
    {
        return match ($this) {
            self::Artworks => 'Artworks',
            self::Categories => 'Categories',
            self::Likes => 'Likes',
            self::Views => 'Views',
            self::Comments => 'Comments',
            self::Users => 'Users',
            self::Messages => 'Messages',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Artworks => 'Published pieces',
            self::Categories => 'Ways to browse the gallery',
            self::Likes => 'Across all artworks',
            self::Views => 'Artwork page visits',
            self::Comments => 'Conversations on artworks',
            self::Users => 'Registered accounts',
            self::Messages => 'Contact form submissions',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Artworks => 'primary',
            self::Categories => 'info',
            self::Likes => 'danger',
            self::Views => 'warning',
            self::Comments => 'success',
            self::Users => 'gray',
            self::Messages => 'primary',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Artworks => '#293241',
            self::Categories => '#98C1D9',
            self::Likes => '#E63946',
            self::Views => '#F4A261',
            self::Comments => '#2A9D8F',
            self::Users => '#6B7280',
            self::Messages => '#3D5A80',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::Artworks => Heroicon::OutlinedPhoto,
            self::Categories => Heroicon::OutlinedTag,
            self::Likes => Heroicon::OutlinedHeart,
            self::Views => Heroicon::OutlinedEye,
            self::Comments => Heroicon::OutlinedChatBubbleLeftRight,
            self::Users => Heroicon::OutlinedUsers,
            self::Messages => Heroicon::OutlinedEnvelope,
        };
    }

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return match ($this) {
            self::Artworks => Artwork::class,
            self::Categories => Category::class,
            self::Likes => Like::class,
            self::Views => ArtworkView::class,
            self::Comments => Comment::class,
            self::Users => User::class,
            self::Messages => ContactMessage::class,
        };
    }

    /**
     * The timestamp used to place a record on the growth chart.
     */
    public function dateColumn(): string
    {
        return match ($this) {
            self::Views => 'viewed_at',
            default => 'created_at',
        };
    }
}

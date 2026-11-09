<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artwork;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Artwork::query()
            ->with(['category', 'media'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }

        $artworks = $query->paginate(18)->withQueryString();
        $categories = \App\Models\Category::query()->orderBy('name')->get();

        return inertia('gallery/index', [
            'artworks' => $artworks,
            'categories' => $categories,
            'selectedCategory' => $request->string('category')->toString(),
        ]);
    }

    public function show(Artwork $artwork): View
    {
        $artwork->load(['category', 'media']);

        // Increment view count
        $artwork->increment('view_count');

        return inertia('gallery/show', [
            'artwork' => [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'description' => $artwork->description,
                'alt_text' => $artwork->alt_text,
                'image_url' => $artwork->getFirstMediaUrl('images') ?? $artwork->image_url,
                'category' => $artwork->category ? [
                    'name' => $artwork->category->name,
                    'slug' => $artwork->category->slug,
                ] : null,
                'view_count' => $artwork->view_count,
                'like_count' => $artwork->like_count,
                'created_at' => $artwork->created_at?->toISOString(),
            ],
        ]);
    }
}

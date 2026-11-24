<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ArtworkCardResource;
use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Render a bounded collection and a separate, small intro selection.
     */
    public function index(Request $request): Response
    {
        $query = Artwork::query()
            ->select(['id', 'category_id', 'title', 'image_url', 'alt_text'])
            ->with(['category:id,name,slug', 'media'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $request->string('category')));
        }

        // Pills follow the design: seed order, and only categories that
        // actually have a painting (no empty filters).
        $categories = Category::query()
            ->whereHas('artworks')
            ->orderBy('id')
            ->get();

        return inertia('welcome', [
            'artworks' => fn () => $query->simplePaginate(18)->withQueryString()
                ->through(fn (Artwork $artwork) => (new ArtworkCardResource($artwork))->resolve()),
            'introArtworks' => fn () => Artwork::query()
                ->select(['id', 'image_url', 'alt_text'])
                ->with('media')->orderByDesc('created_at')->orderBy('id')->limit(12)->get()
                ->map(fn (Artwork $artwork) => (new ArtworkCardResource($artwork))->resolve()),
            'categories' => $categories,
            'selectedCategory' => $request->string('category')->toString(),
        ]);
    }
}

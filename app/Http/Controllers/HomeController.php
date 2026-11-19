<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Render the landing page with every artwork (the design shows the full
     * collection; filtering happens client-side).
     */
    public function index(Request $request): Response
    {
        $query = Artwork::query()
            ->with(['category', 'media'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->string('category'));
            });
        }

        $artworks = $query->paginate(18)->withQueryString();

        // Pills follow the design: seed order, and only categories that
        // actually have a painting (no empty filters).
        $categories = Category::query()
            ->has('artworks')
            ->orderBy('id')
            ->get();

        return inertia('welcome', [
            'artworks' => $artworks,
            'categories' => $categories,
        ]);
    }
}

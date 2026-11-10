<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Render the landing page with every artwork (the design shows the full
     * collection; filtering happens client-side).
     */
    public function index(): Response
    {
        $artworks = Artwork::query()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->get();

        $categories = Category::query()->orderBy('name')->get();

        return inertia('welcome', [
            'artworks' => $artworks,
            'categories' => $categories,
        ]);
    }
}

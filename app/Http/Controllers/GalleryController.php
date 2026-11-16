<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Artwork::query()
            ->with(['category', 'media'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }

        $artworks = $query->paginate(18)->withQueryString();
        // Same pill list as the home page: seed order, artwork-backed only.
        $categories = Category::query()
            ->whereHas('artworks')
            ->orderBy('id')
            ->get();

        return inertia('gallery/index', [
            'artworks' => $artworks,
            'categories' => $categories,
            'selectedCategory' => $request->string('category')->toString(),
        ]);
    }

    public function show(Artwork $artwork): Response
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
                'image_url' => $artwork->getFirstMediaUrl('images') ?: $artwork->image_url,
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

    public function toggleLike(Request $request, Artwork $artwork): JsonResponse
    {
        $user = $request->user();
        $existingLike = Like::where('user_id', $user->id)
            ->where('artwork_id', $artwork->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $artwork->decrement('like_count');
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'artwork_id' => $artwork->id,
            ]);
            $artwork->increment('like_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'like_count' => $artwork->fresh()->like_count,
        ]);
    }

    public function getComments(Request $request, Artwork $artwork): JsonResponse
    {
        $comments = $artwork->comments()
            ->with('user:id,display_name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'display_name' => $comment->user->display_name,
                    ],
                    'created_at' => $comment->created_at?->toISOString(),
                ];
            });

        return response()->json($comments);
    }

    public function storeComment(Request $request, Artwork $artwork): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:280',
        ]);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'artwork_id' => $artwork->id,
            'body' => $request->string('body')->toString(),
        ]);

        $comment->load('user:id,display_name');

        return response()->json([
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => [
                'display_name' => $comment->user->display_name,
            ],
            'created_at' => $comment->created_at?->toISOString(),
        ], 201);
    }
}

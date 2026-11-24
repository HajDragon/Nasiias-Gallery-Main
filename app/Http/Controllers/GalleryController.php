<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordArtworkViewAction;
use App\Actions\StoreCommentAction;
use App\Actions\ToggleLikeAction;
use App\Http\Resources\ArtworkCardResource;
use App\Models\Artwork;
use App\Models\Category;
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
            ->orderByDesc('id');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }

        $artworks = $query->paginate(18)->withQueryString()
            ->through(fn (Artwork $artwork) => (new ArtworkCardResource($artwork))->resolve());
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

    public function show(Request $request, Artwork $artwork, RecordArtworkViewAction $recordView): Response
    {
        $artwork->load(['category', 'media']);

        $recordView($artwork, $request->user());

        return inertia('gallery/show', [
            'artwork' => [
                'id' => $artwork->id,
                'title' => $artwork->title,
                'description' => $artwork->description,
                'alt_text' => $artwork->alt_text,
                ...$artwork->imageSources(detail: true),
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

    public function toggleLike(Request $request, Artwork $artwork, ToggleLikeAction $toggleLike): JsonResponse
    {
        $liked = $toggleLike($request->user(), $artwork);

        return response()->json([
            'liked' => $liked,
            'like_count' => $artwork->like_count,
        ]);
    }

    public function getComments(Request $request, Artwork $artwork): JsonResponse
    {
        $comments = $artwork->comments()
            ->with('user:id,display_name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(20)
            ->withQueryString();

        // Generate cursors from database values before formatting dates for clients.
        $page = $comments->toArray();
        $page['data'] = $comments->getCollection()->map(function ($comment) {
            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => [
                    'display_name' => $comment->user->display_name,
                ],
                'created_at' => $comment->created_at?->toISOString(),
            ];
        });

        return response()->json($page);
    }

    public function storeComment(Request $request, Artwork $artwork, StoreCommentAction $storeComment): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:'.StoreCommentAction::MAX_INPUT_LENGTH,
        ]);

        $comment = $storeComment(
            $request->user(),
            $artwork,
            $request->string('body')->toString(),
        );

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

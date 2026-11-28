<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordArtworkViewAction;
use App\Actions\StoreCommentAction;
use App\Actions\ToggleLikeAction;
use App\Http\Resources\ArtworkCardResource;
use App\Models\Artwork;
use App\Models\Category;
use App\Services\SEOService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class GalleryController extends Controller
{
    public function __construct(
        private SEOService $seoService
    ) {}

    public function index(Request $request): Response
    {
        $query = Artwork::query()
            ->with(['category', 'media'])
            ->orderBy('created_at', 'desc')
            ->orderByDesc('id');

        $selectedCategory = $request->string('category')->toString();
        $category = null;

        if ($selectedCategory) {
            $category = Category::where('slug', $selectedCategory)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            } else {
                // Unknown category: force empty result set
                $query->whereRaw('1 = 0');
            }
        }

        $artworks = $query->paginate(18)->withQueryString()
            ->through(fn (Artwork $artwork) => (new ArtworkCardResource($artwork))->resolve());
        // Same pill list as the home page: seed order, artwork-backed only.
        $categories = Category::query()
            ->has('artworks')
            ->orderBy('id')
            ->get();

        return inertia('gallery/index', [
            'artworks' => $artworks,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'seo' => $this->seoService->getSEOData($category),
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
            'seo' => $this->seoService->getSEOData($artwork),
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
            ->with([
                'user:id,display_name',
                'user.media:id,model_type,model_id,collection_name,file_name,mime_type,disk,conversions_disk,generated_conversions,manipulations,responsive_images',
            ])
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
                    'avatar' => $comment->user->avatar,
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

        $comment->load(['user:id,display_name', 'user.media']);

        return response()->json([
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => [
                'display_name' => $comment->user->display_name,
                'avatar' => $comment->user->avatar,
            ],
            'created_at' => $comment->created_at?->toISOString(),
        ], 201);
    }
}

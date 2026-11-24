<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_bounds_the_collection_and_intro_and_keeps_all_artworks_reachable(): void
    {
        // Arrange: enough records to exceed both limits, with equal timestamps.
        Artwork::factory()->count(40)->create(['created_at' => now()]);

        // Act and assert: deterministic pages neither overlap nor lose records.
        foreach ([1 => 18, 2 => 18, 3 => 4] as $page => $count) {
            $this->get('/?page='.$page)->assertOk()->assertInertia(fn (Assert $response) => $response
                ->has('artworks.data', $count)->has('introArtworks', 12)
                ->where('artworks.data.0.id', ($page - 1) * 18 + 1));
        }
    }

    public function test_home_filters_on_the_server_and_preserves_the_filter_in_next_page_links(): void
    {
        // Arrange: mixed categories where the requested category exceeds one page.
        $category = Category::factory()->create(['slug' => 'chosen']);
        Artwork::factory()->count(20)->for($category)->create();
        Artwork::factory()->count(5)->create();

        // Act: filter using the category slug.
        $response = $this->get('/?category=chosen');

        // Assert: only matching artworks are returned and the filter survives pagination.
        $response->assertInertia(fn (Assert $page) => $page->where('selectedCategory', 'chosen')
            ->has('artworks.data', 18)->where('artworks.data.0.category.slug', 'chosen')
            ->where('artworks.next_page_url', fn ($url) => str_contains($url, 'category=chosen')));
        $this->get('/?category=unknown')->assertInertia(fn (Assert $page) => $page->has('artworks.data', 0));
    }
}

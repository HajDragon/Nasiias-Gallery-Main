<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GalleryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_lists_every_artwork_without_a_filter(): void
    {
        Artwork::factory()->count(3)->create();

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('gallery/index')
                ->where('selectedCategory', '')
                ->has('artworks.data', 3)
            );
    }

    public function test_gallery_filters_artworks_by_category_slug(): void
    {
        // The category name differs from its slug, mirroring "Color Field".
        $category = Category::factory()->create([
            'name' => 'Color Field',
            'slug' => 'color-field',
        ]);
        $matching = Artwork::factory()->for($category)->create();
        Artwork::factory()->create();

        $this->get(route('gallery.index', ['category' => 'color-field']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedCategory', 'color-field')
                ->has('artworks.data', 1)
                ->where('artworks.data.0.id', $matching->id)
            );
    }

    public function test_gallery_returns_no_artworks_for_an_unknown_category(): void
    {
        Artwork::factory()->create();

        $this->get(route('gallery.index', ['category' => 'does-not-exist']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedCategory', 'does-not-exist')
                ->has('artworks.data', 0)
            );
    }
}

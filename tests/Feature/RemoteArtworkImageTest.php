<?php

namespace Tests\Feature;

use App\Models\Artwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoteArtworkImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsplash_sources_resize_remote_artwork_and_preserve_attribution_and_crop(): void
    {
        $artwork = Artwork::factory()->create([
            'image_url' => 'https://images.unsplash.com/photo-example?ixid=keep-me&fit=crop&w=1200&h=900&q=85',
        ]);

        $sources = $artwork->imageSources();
        parse_str(parse_url($sources['image_url'], PHP_URL_QUERY), $query);
        $this->assertSame('600', $query['w']);
        $this->assertSame('450', $query['h']);
        $this->assertSame('keep-me', $query['ixid']);
        $this->assertSame('crop', $query['fit']);
        $this->assertSame('format', $query['auto']);
        $this->assertStringContainsString(' 160w, ', $sources['image_srcset']);
        $this->assertStringContainsString(' 320w, ', $sources['image_srcset']);
        $this->assertStringEndsWith(' 1600w', $sources['image_srcset']);
        parse_str(parse_url($artwork->imageSources(true)['image_url'], PHP_URL_QUERY), $detail);
        $this->assertSame('1600', $detail['w']);
        $this->assertSame('1200', $detail['h']);
    }

    public function test_other_remote_image_urls_are_left_unchanged(): void
    {
        foreach (['https://example.com/painting.jpg?w=1200', 'https://images.unsplash.com.example.com/painting.jpg', '/storage/artwork.jpg'] as $url) {
            $artwork = Artwork::factory()->create(['image_url' => $url]);
            $this->assertSame(['image_url' => $url, 'image_srcset' => null], $artwork->imageSources());
        }
    }
}

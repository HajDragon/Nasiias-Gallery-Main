<?php

namespace Tests\Feature;

use App\Actions\ProcessArtworkImageAction;
use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessArtworkImageActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // Unit-level conversion assertions run immediately; the real queue has its own test.
        config(['media-library.queue_connection_name' => 'sync', 'media-library.queue_conversions_after_database_commit' => false]);
    }

    public function test_processes_and_attaches_uploaded_image_to_artwork(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $artwork = Artwork::factory()->create(['category_id' => $category->id]);
        $file = UploadedFile::fake()->image('painting.jpg', 800, 600);
        $action = new ProcessArtworkImageAction;

        // Act
        $media = $action->execute($artwork, $file);

        // Assert
        $this->assertNotNull($media);
        $this->assertTrue($artwork->hasMedia('images'));
        $this->assertEquals($media->getUrl(), $artwork->fresh()->image_url);
    }

    public function test_artwork_registers_media_conversions(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $artwork = Artwork::factory()->create(['category_id' => $category->id]);
        $file = UploadedFile::fake()->image('canvas.jpg', 1000, 800);
        $action = new ProcessArtworkImageAction;

        // Act
        $media = $action->execute($artwork, $file);

        // Assert
        $media->refresh(); // A queued worker updates its own serialized media instance.
        $this->assertTrue($media->hasGeneratedConversion('compressed'));
        $this->assertTrue($media->hasGeneratedConversion('thumbnail'));
    }

    public function test_single_file_collection_replaces_previous_image(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $artwork = Artwork::factory()->create(['category_id' => $category->id]);
        $firstFile = UploadedFile::fake()->image('first.jpg', 600, 400);
        $secondFile = UploadedFile::fake()->image('second.jpg', 800, 600);
        $action = new ProcessArtworkImageAction;

        // Act
        $action->execute($artwork, $firstFile);
        $secondMedia = $action->execute($artwork, $secondFile);
        $artwork->refresh();

        // Assert
        $this->assertCount(1, $artwork->getMedia('images'));
        $this->assertEquals($secondMedia->id, $artwork->getFirstMedia('images')?->id);
        $this->assertEquals($secondMedia->getUrl(), $artwork->image_url);
    }

    public function test_responsive_sources_preserve_aspect_ratio_and_fall_back_before_conversion(): void
    {
        // Arrange: a portrait image must be scaled without cropping or upscaling.
        $artwork = Artwork::factory()->create();
        $file = UploadedFile::fake()->image('portrait.jpg', 900, 1600);

        // Act: create media and inspect the generated dimensions and public sources.
        $media = (new ProcessArtworkImageAction)->execute($artwork, $file);
        $artwork->refresh();
        $dimensions = getimagesize($media->getPath('card'));

        // Assert: the descriptor matches the real file, and metadata is not exposed.
        $this->assertSame(600, $dimensions[1]);
        $this->assertStringContainsString($dimensions[0].'w', $artwork->imageSources()['image_srcset']);
        $this->assertSame($media->getUrl('card'), $artwork->imageSources()['image_url']);
        $this->assertSame($media->getUrl('display'), $artwork->imageSources(true)['image_url']);
        $media->setAttribute('generated_conversions', []);
        $artwork->setRelation('media', collect([$media]));
        $this->assertSame($media->getUrl(), $artwork->imageSources()['image_url']);
        $this->assertNull($artwork->imageSources()['image_srcset']);
    }
}

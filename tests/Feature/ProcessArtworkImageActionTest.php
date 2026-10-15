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
}

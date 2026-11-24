<?php

namespace Tests\Feature;

use App\Actions\ProcessArtworkImageAction;
use App\Models\Artwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueuedArtworkConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_waits_for_commit_and_keeps_original_available_until_worker_finishes(): void
    {
        // Arrange: exercise a real database queue rather than the test suite's sync driver.
        Storage::fake('public');
        config(['queue.default' => 'database', 'media-library.queue_connection_name' => 'database', 'media-library.queue_name' => 'media']);
        $artwork = Artwork::factory()->create();

        // Act: attach an image inside a transaction, then process it in a worker.
        $media = DB::transaction(function () use ($artwork) {
            $media = (new ProcessArtworkImageAction)->execute($artwork, UploadedFile::fake()->image('queued.jpg', 1200, 800));
            $this->assertDatabaseCount('jobs', 0);

            return $media;
        });

        // Assert: queued processing leaves a working original, then supplies smaller variants.
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame($media->getUrl(), $artwork->fresh()->imageSources()['image_url']);
        $this->assertFalse($media->fresh()->hasGeneratedConversion('card'));
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'media', '--once' => true, '--tries' => 3, '--timeout' => 120]);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertTrue($media->fresh()->hasGeneratedConversion('card'));
        $this->assertTrue($media->fresh()->hasGeneratedConversion('intro'));
        $this->assertSame($media->getUrl('card'), $artwork->fresh()->imageSources()['image_url']);
    }
}

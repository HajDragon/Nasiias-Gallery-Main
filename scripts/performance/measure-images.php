<?php

declare(strict_types=1);
use App\Actions\ProcessArtworkImageAction;
use App\Models\Artwork;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;

// Run prepare-fixture.php first. Compare identical conversion work, not different images.
$app = require __DIR__.'/fixture-bootstrap.php';
$source = storage_path('framework/testing/performance/images/original.jpg');
if (! is_file($source)) {
    throw new RuntimeException('Run prepare-fixture.php first.');
}
$results = [];
foreach (['sync', 'database'] as $connection) {
    config(['media-library.queue_connection_name' => $connection, 'media-library.queue_name' => 'media', 'media-library.queue_conversions_after_database_commit' => true]);
    $samples = [];
    $workerSamples = [];
    for ($i = 0; $i < 6; $i++) {
        $artwork = Artwork::factory()->create(['category_id' => 1]);
        $copy = storage_path('framework/testing/performance/upload-'.$connection.'-'.$i.'.jpg');
        copy($source, $copy);
        $file = new UploadedFile($copy, 'painting.jpg', 'image/jpeg', null, true);
        $start = hrtime(true);
        $media = (new ProcessArtworkImageAction)->execute($artwork, $file);
        $elapsed = (hrtime(true) - $start) / 1e6;
        if ($i > 0) {
            $samples[] = $elapsed;
        }
        if ($connection === 'database') {
            if ($media->fresh()->hasGeneratedConversion('card')) {
                throw new RuntimeException('The async case unexpectedly processed inside the request.');
            }
            $workerStart = hrtime(true);
            Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'media', '--once' => true, '--tries' => 3, '--timeout' => 120]);
            if ($i > 0) {
                $workerSamples[] = (hrtime(true) - $workerStart) / 1e6;
            }
        }
        if (! $media->fresh()->hasGeneratedConversion('card')) {
            throw new RuntimeException('The expected conversion was not produced.');
        }
    }
    $sorted = $samples;
    sort($sorted);
    $results[$connection] = ['request_samples_ms' => $samples, 'request_median_ms' => $sorted[2], 'worker_samples_ms' => $workerSamples];
}
echo json_encode([
    'captured_at_utc' => gmdate('c'), 'php' => PHP_VERSION, 'commit' => trim((string) shell_exec('git rev-parse HEAD')),
    'method' => 'Identical deterministic 2400x1600 JPEG and identical five conversions in both cases; one warmup plus five samples. Sync performs conversion inside the request, database queues it and times worker separately. This compares execution strategies on the optimized code, not the old release against the new release. Worker time remains real server CPU work.',
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;

<?php

declare(strict_types=1);
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Models\Artwork;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Run from the repository root: php scripts/performance/measure.php > result.json
// This probe uses only synthetic data in an isolated in-memory database.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'TELESCOPE_ENABLED' => 'false'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
foreach (['APP_CONFIG_CACHE' => 'no-config-cache.php', 'APP_ROUTES_CACHE' => 'no-routes-cache.php'] as $key => $name) {
    $value = dirname(__DIR__, 2).'/storage/framework/testing/performance/'.$name;
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'telescope.enabled' => false]);
DB::purge('sqlite');
$db = DB::connection();
if ($db->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Only an in-memory database is allowed.');
}
Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
$now = '2026-11-24 12:00:00';
$db->table('categories')->insert(['id' => 1, 'name' => 'Audit', 'slug' => 'audit', 'created_at' => $now, 'updated_at' => $now]);
$db->table('users')->insert(['id' => 1, 'display_name' => 'Synthetic User', 'email' => 'audit@example.invalid', 'password_hash' => 'unused', 'created_at' => $now, 'updated_at' => $now]);

function measure(string $name, int $size, callable $run): array
{
    $run();
    $run();
    $times = [];
    $db = DB::connection();
    for ($i = 0; $i < 9; $i++) {
        $db->flushQueryLog();
        $db->enableQueryLog();
        $start = hrtime(true);
        $response = $run();
        $times[] = (hrtime(true) - $start) / 1e6;
        $bytes = strlen($response->getContent());
        $queries = count($db->getQueryLog());
        $db->disableQueryLog();
    }
    $sorted = $times;
    sort($sorted);

    return ['case' => $name, 'records' => $size, 'queries' => $queries, 'json_bytes' => $bytes, 'samples_ms' => $times, 'median_ms' => $sorted[4], 'max_ms' => max($times)];
}

$results = [];
foreach ([20, 200, 1000] as $size) {
    $db->table('artworks')->delete();
    $rows = [];
    for ($id = 1; $id <= $size; $id++) {
        $rows[] = ['id' => $id, 'category_id' => 1, 'title' => 'Synthetic artwork '.$id, 'description' => str_repeat('Description ', 20), 'image_url' => '/synthetic/image-'.$id.'.jpg', 'alt_text' => 'Synthetic artwork', 'created_at' => $now, 'updated_at' => $now];
    }
    foreach (array_chunk($rows, 100) as $chunk) {
        $db->table('artworks')->insert($chunk);
    }
    foreach (['home' => '/', 'gallery' => '/gallery'] as $name => $uri) {
        $request = Request::create($uri, 'GET');
        $request->headers->set('X-Inertia', 'true');
        $app->instance('request', $request);
        $controller = $name === 'home' ? new HomeController : new GalleryController;
        $results[] = measure($name, $size, fn () => $app->call([$controller, 'index'])->toResponse($request));
    }
}
$artwork = Artwork::findOrFail(1);
foreach ([20, 200, 1000] as $size) {
    $db->table('comments')->delete();
    $rows = [];
    for ($id = 1; $id <= $size; $id++) {
        $rows[] = ['user_id' => 1, 'artwork_id' => 1, 'body' => str_repeat('Comment ', 25), 'created_at' => $now, 'updated_at' => $now];
    }
    foreach (array_chunk($rows, 100) as $chunk) {
        $db->table('comments')->insert($chunk);
    }
    $request = Request::create('/gallery/1/comments', 'GET');
    $app->instance('request', $request);
    $results[] = measure('comments', $size, fn () => $app->call([new GalleryController, 'getComments'], ['artwork' => $artwork]));
}
Cache::flush();
$request = Request::create('/gallery/1', 'GET');
$request->headers->set('X-Inertia', 'true');
$app->instance('request', $request);
$db->flushQueryLog();
$db->enableQueryLog();
for ($i = 0; $i < 20; $i++) {
    $app->call([new GalleryController, 'show'], ['artwork' => Artwork::findOrFail(1)])->toResponse($request);
}
$viewWrites = count(array_filter($db->getQueryLog(), fn ($query) => preg_match('/^\s*(insert|update)/i', $query['query'])));
$db->disableQueryLog();
$plans = [];
foreach (['artworks' => 'select * from artworks order by created_at desc, id desc limit 18', 'comments' => 'select * from comments where artwork_id = 1 order by created_at desc, id desc limit 20'] as $name => $sql) {
    $plans[$name] = $db->select('EXPLAIN QUERY PLAN '.$sql);
}
$assets = [];
$manifestPath = __DIR__.'/../../public/build/manifest.json';
if (is_file($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    foreach (['resources/css/app.css', 'resources/js/app.tsx'] as $entry) {
        $contents = file_get_contents(__DIR__.'/../../public/build/'.$manifest[$entry]['file']);
        $assets[$entry] = ['bytes' => strlen($contents), 'gzip_bytes_estimate' => strlen(gzencode($contents))];
    }
}
echo json_encode([
    'captured_at_utc' => gmdate('c'), 'commit' => trim((string) shell_exec('git rev-parse HEAD')),
    'php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'database' => 'SQLite :memory:',
    'method' => 'Controller and JSON serialization only; 2 warmups and 9 timed repetitions. Excludes middleware, network, browser and image downloads. One synthetic category, descriptions 240 ASCII characters, comments 200 ASCII characters. Asset sizes require a fresh build. No production data.',
    'measurements' => $results, 'repeated_detail_requests' => 20, 'detail_write_queries' => $viewWrites, 'query_plans' => $plans, 'assets' => $assets,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

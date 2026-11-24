<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;

// Dedicated synthetic fixture. Never uses the developer's database, uploads or sessions.
$project = dirname(__DIR__, 2);
$fixture = $project.'/storage/framework/testing/performance';
if (! is_dir($fixture)) {
    mkdir($fixture, 0777, true);
}
foreach (['APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8787', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)), 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $fixture.'/fixture.sqlite', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'file', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array', 'TELESCOPE_ENABLED' => 'false'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
foreach (['APP_CONFIG_CACHE' => $fixture.'/no-config-cache.php', 'APP_ROUTES_CACHE' => $fixture.'/no-routes-cache.php'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require $project.'/vendor/autoload.php';
$app = require $project.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $fixture.'/fixture.sqlite', 'database.connections.sqlite.url' => null,
    'filesystems.disks.public.root' => $fixture.'/images', 'filesystems.disks.public.url' => '/audit-images',
    'session.files' => $fixture.'/sessions', 'media-library.queue_connection_name' => 'sync', 'media-library.queue_conversions_after_database_commit' => false,
]);
foreach (['images', 'sessions'] as $directory) {
    if (! is_dir($fixture.'/'.$directory)) {
        mkdir($fixture.'/'.$directory, 0777, true);
    }
}
DB::purge('sqlite');
Vite::useHotFile($fixture.'/no-hot-file');

return $app;

<?php

declare(strict_types=1);
use Illuminate\Http\Request;

// Start only on loopback: php -S 127.0.0.1:8787 -t public scripts/performance/browser-router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$public = realpath(dirname(__DIR__, 2).'/public');
$asset = realpath($public.$path);
if ($asset !== false && str_starts_with($asset, $public.DIRECTORY_SEPARATOR) && is_file($asset)) {
    return false;
}
if (str_starts_with($path, '/audit-images/')) {
    $root = realpath(dirname(__DIR__, 2).'/storage/framework/testing/performance/images');
    $file = realpath($root.'/'.substr($path, strlen('/audit-images/')));
    if ($file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file)) {
        header('Content-Type: '.mime_content_type($file));
        readfile($file);
    } else {
        http_response_code(404);
    }

    return;
}
$app = require __DIR__.'/fixture-bootstrap.php';
$app->handleRequest(Request::capture());

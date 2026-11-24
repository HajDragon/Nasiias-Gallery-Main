<?php

use App\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/{artwork}', [GalleryController::class, 'show'])->name('gallery.show');

    Route::middleware(['auth'])->group(function () {
        Route::post('/gallery/{artwork}/like', [GalleryController::class, 'toggleLike'])->middleware('throttle:likes-write')->name('gallery.like');
        Route::get('/gallery/{artwork}/comments', [GalleryController::class, 'getComments'])->middleware('throttle:comments-read')->name('gallery.comments.index');
        Route::post('/gallery/{artwork}/comments', [GalleryController::class, 'storeComment'])->middleware('throttle:comments-write')->name('gallery.comments.store');
    });
});

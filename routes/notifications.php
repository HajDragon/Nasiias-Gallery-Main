<?php

use App\Http\Controllers\NewArtworkNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::put('/notifications/new-artwork', [NewArtworkNotificationController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('notifications.new-artwork.update');
});

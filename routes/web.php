<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:5,1')->name('contact.store');
Route::inertia('design-test', 'design-test')->name('design-test');

require __DIR__.'/settings.php';
require __DIR__.'/gallery.php';
require __DIR__.'/notifications.php';

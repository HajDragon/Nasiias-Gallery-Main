<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::inertia('design-test', 'design-test')->name('design-test');

Route::post('locale/{locale}', LocaleController::class)->name('locale.update');

require __DIR__.'/settings.php';
require __DIR__.'/gallery.php';
require __DIR__.'/notifications.php';

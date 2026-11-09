<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::inertia('design-test', 'design-test')->name('design-test');

require __DIR__.'/settings.php';
require __DIR__.'/gallery.php';

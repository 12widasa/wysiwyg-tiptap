<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/content/create', [ContentController::class, 'create'])->name('content.create');
Route::get('/content/{content}', [ContentController::class, 'show'])->name('content.show');

// Rate limited: store maks 30x/menit, upload maks 20x/menit per IP
Route::post('/content', [ContentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('content.store');

Route::post('/content/upload-image', [ContentController::class, 'uploadImage'])
    ->middleware('throttle:20,1')
    ->name('content.upload-image');

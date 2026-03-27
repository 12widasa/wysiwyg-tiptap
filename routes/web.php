<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/content/create', [ContentController::class, 'create'])->name('content.create');
Route::post('/content', [ContentController::class, 'store'])->name('content.store');
Route::get('/content/{content}', [ContentController::class, 'show'])->name('content.show');
Route::post('/content/upload-image', [ContentController::class, 'uploadImage'])->name('content.upload-image');
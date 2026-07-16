<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PublicController;
use App\Http\Controllers\PageController;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/halaman/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/preview/halaman/{slug}', [PageController::class, 'preview'])->name('pages.preview')->middleware('auth');

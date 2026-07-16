<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/halaman/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/preview/halaman/{slug}', [PageController::class, 'preview'])->name('pages.preview')->middleware('auth');

use App\Http\Controllers\Public\DocumentController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\NewsController;

Route::prefix('berita')->name('news.')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/preview/{slug}', [NewsController::class, 'preview'])->name('preview')->middleware('auth');
    Route::get('/{slug}', [NewsController::class, 'show'])->name('show');
});

Route::prefix('galeri')->name('gallery.')->group(function () {
    Route::get('/', [GalleryController::class, 'index'])->name('index');
    Route::get('/preview/{slug}', [GalleryController::class, 'preview'])->name('preview')->middleware('auth');
    Route::get('/{slug}', [GalleryController::class, 'show'])->name('show');
});

Route::prefix('dokumen')->name('documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/preview/{slug}/download', [DocumentController::class, 'preview'])->name('preview')->middleware('auth');
    Route::get('/{slug}/download', [DocumentController::class, 'download'])->name('download');
});

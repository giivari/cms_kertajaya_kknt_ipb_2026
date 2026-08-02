<?php

use App\Http\Controllers\Admin\AdminPdfExportDownloadController;
use App\Http\Controllers\Admin\PreviewController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DocumentController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/halaman/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/preview/halaman/{slug}', [PageController::class, 'preview'])->name('pages.preview')->middleware('auth');

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

Route::get('/pencarian', [SearchController::class, 'index'])->name('public.search');

Route::prefix('peta')->name('public.map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
    Route::get('/{location:slug}', [MapController::class, 'show'])->name('show');
});

Route::prefix('kontak')->name('public.contact.')->group(function () {
    Route::get('/', [ContactController::class, 'show'])->name('show');
    Route::post('/', [ContactController::class, 'store'])->name('store')->middleware('throttle:contact-submissions');
});

Route::get(config('village.admin_path', 'desa-dashboard') . '/preview/{token}', [PreviewController::class, 'show'])
    ->name('admin.preview.show')
    ->middleware([
        'panel:admin',
        \Filament\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\ForcePasswordChange::class,
        \App\Http\Middleware\AbsoluteSessionTimeout::class,
    ]);

Route::get(config('village.admin_path', 'desa-dashboard').'/exports/pdf/{export}', AdminPdfExportDownloadController::class)
    ->whereNumber('export')
    ->name('admin.exports.pdf.download')
    ->middleware([
        'panel:admin',
        \Filament\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\ForcePasswordChange::class,
        \App\Http\Middleware\AbsoluteSessionTimeout::class,
    ]);

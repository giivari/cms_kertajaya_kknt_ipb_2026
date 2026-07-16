<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;

class GalleryController extends Controller
{
    public function index()
    {
        $albums = GalleryAlbum::published()->with('coverMedia')->latest('published_at')->paginate(12);

        return view('public.gallery.index', compact('albums'));
    }

    public function show($slug)
    {
        $album = GalleryAlbum::published()->with(['items.media.derivatives'])->where('slug', $slug)->firstOrFail();

        return view('public.gallery.show', compact('album'));
    }

    public function preview($slug)
    {
        $album = GalleryAlbum::with(['items.media.derivatives'])->where('slug', $slug)->firstOrFail();

        return view('public.gallery.show', compact('album'));
    }
}

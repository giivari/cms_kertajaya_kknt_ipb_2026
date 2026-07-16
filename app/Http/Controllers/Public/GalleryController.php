<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index()
    {
        $albums = \App\Models\GalleryAlbum::published()->with('coverMedia')->latest('published_at')->paginate(12);
        return view('public.gallery.index', compact('albums'));
    }

    public function show($slug)
    {
        $album = \App\Models\GalleryAlbum::published()->with(['coverMedia', 'items.media'])->where('slug', $slug)->firstOrFail();
        return 'Test Album';
    }
}

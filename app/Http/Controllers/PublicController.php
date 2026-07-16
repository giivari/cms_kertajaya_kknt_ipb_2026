<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function index()
    {
        $latestNews = \App\Models\News::published()->with(['category', 'featuredMedia'])->latest('published_at')->take(3)->get();
        $latestAlbums = \App\Models\GalleryAlbum::published()->with('coverMedia')->latest('published_at')->take(4)->get();
        $latestDocuments = \App\Models\Document::published()->with(['category', 'thumbnailMedia'])->latest('published_at')->take(3)->get();

        return view('home', compact('latestNews', 'latestAlbums', 'latestDocuments'));
    }
}

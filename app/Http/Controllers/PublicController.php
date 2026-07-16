<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\News;

class PublicController extends Controller
{
    public function index()
    {
        $latestNews = News::published()->with(['category', 'featuredMedia'])->latest('published_at')->take(3)->get();
        $latestAlbums = GalleryAlbum::published()->with('coverMedia')->latest('published_at')->take(4)->get();
        $latestDocuments = Document::published()->with(['category', 'thumbnailMedia'])->latest('published_at')->take(3)->get();

        return view('home', compact('latestNews', 'latestAlbums', 'latestDocuments'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\News;

class PublicController extends Controller
{
    public function index()
    {
        $featuredNews = News::published()->where('is_featured', true)->with(['category', 'featuredMedia'])->latest('published_at')->first();
        
        if ($featuredNews) {
            $otherNews = News::published()->where('id', '!=', $featuredNews->id)->with(['category', 'featuredMedia'])->latest('published_at')->take(2)->get();
            $latestNews = collect([$featuredNews])->merge($otherNews);
        } else {
            $latestNews = News::published()->with(['category', 'featuredMedia'])->latest('published_at')->take(3)->get();
        }
        $latestAlbums = GalleryAlbum::published()->with('coverMedia')->latest('published_at')->take(4)->get();
        $latestDocuments = Document::published()->with(['category', 'thumbnailMedia'])->latest('published_at')->take(3)->get();

        return view('home', array_merge(compact('latestNews', 'latestAlbums', 'latestDocuments'), ['isHome' => true]));
    }
}

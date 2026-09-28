<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\News;
use App\Models\Page;

class PublicController extends Controller
{
    public function index()
    {
        $featuredNews = News::published()->where('is_featured', true)->with(['category', 'featuredMedia.derivatives'])->latest('published_at')->first();
        
        if ($featuredNews) {
            $otherNews = News::published()->where('id', '!=', $featuredNews->id)->with(['category', 'featuredMedia.derivatives'])->latest('published_at')->take(2)->get();
            $latestNews = collect([$featuredNews])->merge($otherNews);
        } else {
            $latestNews = News::published()->with(['category', 'featuredMedia.derivatives'])->latest('published_at')->take(3)->get();
        }
        $featuredPages = Page::published()->where('is_featured', true)
            ->with('featuredMedia.derivatives')->orderByDesc('published_at')->orderByDesc('id')->take(3)->get();
        $featuredAlbums = GalleryAlbum::published()->where('is_featured', true)
            ->with('coverMedia.derivatives')->orderByDesc('published_at')->orderByDesc('id')->take(3)->get();
        $latestAlbums = $featuredAlbums->concat(
            GalleryAlbum::published()->whereNotIn('id', $featuredAlbums->modelKeys())
                ->with('coverMedia.derivatives')->orderByDesc('published_at')->orderByDesc('id')
                ->take(3 - $featuredAlbums->count())->get()
        );
        $latestDocuments = Document::published()->with(['category', 'thumbnailMedia'])->latest('published_at')->take(3)->get();

        return view('home', array_merge(compact('latestNews', 'latestAlbums', 'latestDocuments', 'featuredPages'), ['isHome' => true]));
    }
}

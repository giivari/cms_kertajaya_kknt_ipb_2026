<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = \App\Models\News::published()->with(['category', 'featuredMedia'])->latest('published_at')->paginate(12);
        return view('public.news.index', compact('news'));
    }

    public function show($slug)
    {
        $newsItem = \App\Models\News::published()->with(['category', 'featuredMedia'])->where('slug', $slug)->firstOrFail();
        return view('public.news.show', compact('newsItem'));
    }
}

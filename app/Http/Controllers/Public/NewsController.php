<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::published()->with(['category', 'featuredMedia']);

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $news = $query->latest('published_at')->paginate(12);

        return view('public.news.index', compact('news'));
    }

    public function show($slug)
    {
        $newsItem = News::published()->with(['category', 'featuredMedia'])->where('slug', $slug)->firstOrFail();

        return view('public.news.show', compact('newsItem'));
    }

    public function preview($slug)
    {
        $newsItem = News::with(['category', 'featuredMedia'])->where('slug', $slug)->firstOrFail();

        return view('public.news.show', compact('newsItem'));
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));

        // Validate query length
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) {
            return view('public.search', [
                'query' => $query,
                'pages' => collect(),
                'news' => collect(),
                'documents' => collect(),
                'totalCount' => 0,
            ]);
        }

        // Sanitize: strip control characters, keep only printable content
        $query = preg_replace('/[\x00-\x1F\x7F]/u', '', $query);

        // PostgreSQL case-insensitive search using ILIKE
        $likeQuery = '%' . str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query) . '%';

        // Pages: search title and excerpt (published only, not soft-deleted)
        $pages = Page::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('deleted_at')
            ->where(function ($q) use ($likeQuery) {
                $q->whereRaw('title ILIKE ?', [$likeQuery])
                  ->orWhereRaw('excerpt ILIKE ?', [$likeQuery]);
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        // News: search title, excerpt, and content (published only, not soft-deleted)
        $news = News::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('deleted_at')
            ->where(function ($q) use ($likeQuery) {
                $q->whereRaw('title ILIKE ?', [$likeQuery])
                  ->orWhereRaw('excerpt ILIKE ?', [$likeQuery])
                  ->orWhereRaw('content ILIKE ?', [$likeQuery]);
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        // Documents: search title and description (published only, not soft-deleted)
        $documents = Document::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('deleted_at')
            ->where(function ($q) use ($likeQuery) {
                $q->whereRaw('title ILIKE ?', [$likeQuery])
                  ->orWhereRaw('description ILIKE ?', [$likeQuery]);
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'description', 'published_at']);

        $totalCount = $pages->count() + $news->count() + $documents->count();

        return view('public.search', [
            'query' => $query,
            'pages' => $pages,
            'news' => $news,
            'documents' => $documents,
            'totalCount' => $totalCount,
        ]);
    }
}

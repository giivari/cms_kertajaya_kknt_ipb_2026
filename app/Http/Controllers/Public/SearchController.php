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
        $rawQuery = $request->query('q', '');
        $query = is_string($rawQuery) && mb_check_encoding($rawQuery, 'UTF-8')
            ? trim($rawQuery)
            : '';

        // Remove control characters before calculating the public input limit.
        $query = preg_replace('/[\x00-\x1F\x7F]/u', '', $query) ?? '';

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

        // PostgreSQL case-insensitive search using ILIKE
        // Escape the escape character first so literal %, _, and backslashes
        // remain literals in PostgreSQL's default ILIKE escape mode.
        $likeQuery = '%' . str_replace(['%', '_'], ['\\%', '\\_'], str_replace('\\', '\\\\', $query)) . '%';

        // Pages: search title and excerpt (published only, not soft-deleted)
        $pages = Page::published()
            ->where(function ($q) use ($likeQuery) {
                $q->whereRaw('title ILIKE ?', [$likeQuery])
                  ->orWhereRaw('excerpt ILIKE ?', [$likeQuery]);
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        // News: search title, excerpt, and content (published only, not soft-deleted)
        $news = News::published()
            ->where(function ($q) use ($likeQuery) {
                $q->whereRaw('title ILIKE ?', [$likeQuery])
                  ->orWhereRaw('excerpt ILIKE ?', [$likeQuery])
                  ->orWhereRaw('content ILIKE ?', [$likeQuery]);
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        // Documents: search title and description (published only, not soft-deleted)
        $documents = Document::published()
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

<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->with(['sections.components'])
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('pages.dynamic', compact('page'));
    }

    public function preview($slug)
    {
        if (! auth()->check()) {
            abort(404);
        }

        $page = Page::where('slug', $slug)
            ->with(['sections.components'])
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('pages.dynamic', compact('page'));
    }
}

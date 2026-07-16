<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', PageStatus::PUBLISHED->value)
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

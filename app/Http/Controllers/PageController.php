<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', PageStatus::PUBLISHED->value)
            ->with(['sections.components.section'])
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('pages.dynamic', compact('page'));
    }
}

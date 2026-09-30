<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\Location;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            rtrim(route('home'), '/').'/',
            route('news.index'),
            route('gallery.index'),
            route('documents.index'),
            route('public.map.index'),
            route('public.contact.show'),
        ])
            ->concat(Page::published()->orderBy('id')->pluck('slug')->map(
                fn (string $slug): string => route('pages.show', $slug),
            ))
            ->concat(News::published()->orderBy('id')->pluck('slug')->map(
                fn (string $slug): string => route('news.show', $slug),
            ))
            ->concat(GalleryAlbum::published()->orderBy('id')->pluck('slug')->map(
                fn (string $slug): string => route('gallery.show', $slug),
            ))
            ->concat(Location::publiclyVisible()->orderBy('id')->get()->map(
                fn (Location $location): string => route('public.map.show', $location),
            ))
            ->unique()
            ->values();

        return response()
            ->view('public.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $categories = LocationCategory::query()
            ->where('is_active', true)
            ->whereHas('locations', fn (Builder $query) => $query->publiclyVisible())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedCategory = $request->string('category')->trim()->toString();

        $locations = Location::query()
            ->publiclyVisible()
            ->when($selectedCategory !== '', function (Builder $query) use ($selectedCategory) {
                $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $selectedCategory));
            })
            ->with([
                'category',
                'media' => fn ($query) => $query->approvedImages(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('public.map.index', compact('categories', 'locations', 'selectedCategory'));
    }

    public function show(Location $location)
    {
        abort_unless($location->isPubliclyVisible(), 404);

        $location->load([
            'category',
            'media' => fn ($query) => $query->approvedImages(),
        ]);

        return view('public.map.show', compact('location'));
    }
}

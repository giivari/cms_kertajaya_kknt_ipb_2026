<?php

namespace App\Filament\Providers;

use Filament\Facades\Filament;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Support\Str;

class GlobalSearchProvider extends DefaultGlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $results = parent::getResults($query) ?: GlobalSearchResults::make();
        $queryLower = Str::lower($query);
        if ($queryLower === '') {
            return $results;
        }

        $category = 'Halaman & Fitur';
        $navigationResults = collect($results->getCategories()->get($category, []))->all();
        $seen = [];
        foreach ($navigationResults as $result) {
            $seen[$result->url.'|'.(string) $result->title] = true;
        }

        $navigationGroups = Filament::getNavigation();

        foreach ($navigationGroups as $group) {
            foreach ($group->getItems() as $item) {
                $label = $item->getLabel();
                $url = $item->getUrl();
                $key = $url.'|'.$label;

                if ($item->isVisible() && filled($url) && Str::contains(Str::lower($label), $queryLower) && ! isset($seen[$key])) {
                    $navigationResults[] = new GlobalSearchResult(
                        title: $label,
                        url: $url,
                        details: ['Grup' => $group->getLabel() ?: 'Menu Utama'],
                    );
                    $seen[$key] = true;

                    if (count($navigationResults) >= 20) {
                        break 2;
                    }
                }
            }
        }

        if ($navigationResults) {
            $results->category($category, $navigationResults);
        }

        return $results;
    }
}

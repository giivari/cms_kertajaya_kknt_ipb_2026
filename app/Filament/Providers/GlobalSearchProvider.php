<?php

namespace App\Filament\Providers;

use Filament\Facades\Filament;
use Filament\GlobalSearch\DefaultGlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Support\Str;

class GlobalSearchProvider extends DefaultGlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        // 1. Get results from the default provider (which searches models/resources)
        $results = parent::getResults($query) ?: GlobalSearchResults::make();

        // 2. Add custom search for navigation items (Features, Pages, etc)
        $queryLower = Str::lower($query);
        $navigationGroups = Filament::getNavigation();

        foreach ($navigationGroups as $group) {
            foreach ($group->getItems() as $item) {
                $label = $item->getLabel();
                
                // If the navigation label matches the search query
                if (Str::contains(Str::lower($label), $queryLower)) {
                    $results->category('Halaman & Fitur', [
                        new GlobalSearchResult(
                            title: $label,
                            url: $item->getUrl(),
                            details: ['Grup' => $group->getLabel() ?: 'Menu Utama'],
                        ),
                    ]);
                }
            }
        }

        return $results;
    }
}

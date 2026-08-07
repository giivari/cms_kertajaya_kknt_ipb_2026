<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();
        
        $supported = \App\Models\Menu::supportedLocations();
        foreach ($supported as $location => $name) {
            \App\Models\Menu::firstOrCreate(
                ['location' => $location],
                [
                    'name' => $name,
                    'description' => 'Navigasi untuk ' . strtolower($name)
                ]
            );
        }
    }
}

<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Models\Menu;

class EditFooterMenu extends EditMenu
{
    protected function menuLocation(): string
    {
        return Menu::FOOTER;
    }
}

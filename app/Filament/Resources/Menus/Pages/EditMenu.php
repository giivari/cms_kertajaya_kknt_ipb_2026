<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Support\Concerns\HasEditPreview;
use App\Models\Menu;
use App\Services\ScopedPositionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    use HasEditPreview { afterSave as clearPreviewDraftAfterSave; }

    protected ?bool $hasDatabaseTransactions = true;

    protected function menuLocation(): string
    {
        return Menu::HEADER;
    }

    public function mount(int|string $record = null): void
    {
        $menu = $record !== null
            ? Menu::query()->findOrFail($record)
            : Menu::query()->where('location', $this->menuLocation())->first();
        if (! $menu) {
            abort_unless(MenuResource::canCreate(), 403);
            // Filament's record authorization hook still runs after mount on
            // redirects; provide an unsaved model without writing business data.
            $this->record = new Menu(['location' => $this->menuLocation()]);
            $this->redirect(MenuResource::getUrl('create', ['location' => $this->menuLocation()]));
            $this->skipRender();

            return;
        }

        parent::mount($menu->id);
    }

    protected static string $resource = MenuResource::class;

    protected function previewType(): string
    {
        return 'menu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switchMenu')
                ->label($this->menuLocation() === Menu::HEADER ? 'Kelola Menu Kaki Halaman' : 'Kelola Navigasi Utama')
                ->url(MenuResource::getUrl($this->menuLocation() === Menu::HEADER ? 'footer' : 'index')),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            \App\Filament\Support\PreviewAction::make($this->previewType(), true),
            $this->getCancelFormAction(),
        ];
    }

    protected function beforeSave(): void
    {
        // Both top-level and child items are scoped by menu/parent. Reserving
        // all positions while the menu is locked avoids intermediate collisions
        // as Filament persists the nested repeaters.
        app(ScopedPositionService::class)->reserveMenuItemPositions($this->record);
    }

    protected function afterSave(): void
    {
        \App\Services\AuditLogService::log('menu_updated', $this->record);
        $this->clearPreviewDraftAfterSave();
    }
}

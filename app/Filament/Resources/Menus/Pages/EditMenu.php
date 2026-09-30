<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Support\Concerns\HasEditPreview;
use App\Models\Menu;
use App\Services\ScopedPositionService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    use HasEditPreview { afterSave as clearPreviewDraftAfterSave; }

    protected ?bool $hasDatabaseTransactions = true;

    public function mount(int|string $record = null): void
    {
        $menu = Menu::query()->where('location', Menu::HEADER)->first();
        if (! $menu) {
            abort_unless(MenuResource::canCreate(), 403);
            // Filament's record authorization hook still runs after mount on
            // redirects; provide an unsaved model without writing business data.
            $this->record = new Menu(['location' => Menu::HEADER]);
            $this->redirect(MenuResource::getUrl('create'));
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

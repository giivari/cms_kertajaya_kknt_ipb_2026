<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use App\Models\Menu;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMenu extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use HasCreatePreview { afterCreate as clearPreviewDraftAfterCreate; }

    protected static string $resource = MenuResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function mount(): void
    {
        $location = request()->query('location', Menu::HEADER);
        abort_unless(is_string($location) && array_key_exists($location, Menu::supportedLocations()), 404);

        if (Menu::query()->where('location', $location)->exists()) {
            $this->redirect(MenuResource::getUrl($location === Menu::HEADER ? 'index' : 'footer'));

            return;
        }

        parent::mount();
        $this->form->fill(['location' => $location]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        // The unique location index is the final arbiter for simultaneous creates.
        abort_if(Menu::query()->where('location', $data['location'])->exists(), 409);

        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return MenuResource::getUrl($this->record->location === Menu::HEADER ? 'index' : 'footer');
    }

    protected function previewType(): string
    {
        return 'menu';
    }

    protected function afterCreate(): void
    {
        \App\Services\AuditLogService::log('menu_created', $this->record);
        $this->clearPreviewDraftAfterCreate();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            \App\Filament\Support\PreviewAction::make($this->previewType()),
            $this->getCancelFormAction(),
        ];
    }
}

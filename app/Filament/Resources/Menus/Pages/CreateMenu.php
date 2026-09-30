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
        if (Menu::query()->where('location', Menu::HEADER)->exists()) {
            $this->redirect(MenuResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['location'] = Menu::HEADER;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // The unique location index is the final arbiter for simultaneous creates.
        abort_if(Menu::query()->where('location', Menu::HEADER)->exists(), 409);
        $data['location'] = Menu::HEADER;

        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return MenuResource::getUrl('index');
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

<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\Concerns\HasEditPreview;
use App\Filament\Support\PreviewAction;
use App\Services\PageBuilderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasEditPreview;

    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Ubah Halaman';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui isi halaman tanpa mengubah alamat publik yang sudah digunakan.';
    }

    protected function previewType(): string
    {
        return 'page';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus Halaman')
                ->modalDescription('Halaman akan dihapus dan tautan publiknya tidak lagi dapat dibuka.')
                ->modalSubmitActionLabel('Hapus'),
            ForceDeleteAction::make()->label('Hapus Permanen'),
            RestoreAction::make()->label('Pulihkan'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $service = app(PageBuilderService::class);
        $data['builder_sections'] = $service->reconstructBuilderState($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->builderSections = $data['builder_sections'] ?? [];
        unset($data['builder_sections']);

        return $data;
    }

    protected function afterSave(): void
    {
        $service = app(PageBuilderService::class);
        $service->saveSectionsAndComponents($this->record, $this->builderSections ?? []);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan halaman berhasil disimpan';
    }
}

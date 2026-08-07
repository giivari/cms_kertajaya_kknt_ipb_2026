<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use App\Services\PageBuilderService;
use App\Services\PageTemplateService;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasCreatePreview;

    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Buat Halaman Baru';
    }

    public function getSubheading(): ?string
    {
        return 'Susun informasi halaman, status publikasi, dan bagian kontennya.';
    }

    protected function previewType(): string
    {
        return 'page';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->builderSections = $data['builder_sections'] ?? [];
        $this->selectedTemplate = $data['template'] ?? 'blank';
        unset($data['builder_sections'], $data['template']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $service = app(PageBuilderService::class);

        $sections = $this->builderSections ?? [];
        if (empty($sections) && ! empty($this->selectedTemplate) && $this->selectedTemplate !== 'blank') {
            $templateService = app(PageTemplateService::class);
            $sections = $templateService->getTemplateDefinition($this->selectedTemplate);
        }

        $service->saveSectionsAndComponents($this->record, $sections);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Halaman berhasil dibuat';
    }
}

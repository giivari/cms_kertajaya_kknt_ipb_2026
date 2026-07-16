<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Services\PageBuilderService;
use App\Services\PageTemplateService;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->builderSections = $data['builder_sections'] ?? [];
        $this->selectedTemplate = $data['template'] ?? 'blank';
        unset($data['builder_sections'], $data['template']);

        if ($data['status'] === PageStatus::PUBLISHED->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

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
}

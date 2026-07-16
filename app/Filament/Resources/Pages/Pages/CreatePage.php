<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->builderSections = $data['builder_sections'] ?? [];
        unset($data['builder_sections']);

        if ($data['status'] === \App\Enums\PageStatus::PUBLISHED->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $service = app(\App\Services\PageBuilderService::class);
        $service->saveSectionsAndComponents($this->record, $this->builderSections ?? []);
    }
}

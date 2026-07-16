<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Services\PageBuilderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->url(fn () => route('pages.preview', $this->record->slug))
                ->openUrlInNewTab()
                ->color('gray')
                ->icon('heroicon-o-eye'),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
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

        if ($data['status'] === PageStatus::PUBLISHED->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $service = app(PageBuilderService::class);
        $service->saveSectionsAndComponents($this->record, $this->builderSections ?? []);
    }
}

<?php

namespace App\Filament\Resources\Media\Tables;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Jobs\ProcessMediaJob;
use App\Services\MediaDeletionService;
use App\Services\MediaUsageService;
use App\Services\WatermarkVerificationService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('filename')
                    ->label('Preview')
                    ->disk('public')
                    ->state(function ($record) {
                        return 'media/'.$record->filename;
                    })
                    ->defaultImageUrl(url('/placeholder.png'))
                    ->square(),
                TextColumn::make('original_filename')
                    ->label('Original Filename')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filename')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->sortable(),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processing_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('invisible_watermark_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('mime_type')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/webp' => 'WebP',
                        'application/pdf' => 'PDF',
                    ]),
                SelectFilter::make('processing_status')
                    ->options(MediaProcessingStatus::class),
                SelectFilter::make('invisible_watermark_status')
                    ->options(InvisibleWatermarkStatus::class),
            ])
            ->actions([
                EditAction::make(),
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($record, WatermarkVerificationService $service) {
                        $derivative = $record->derivatives()->where('derivative_type', 'public')->first();
                        if ($derivative) {
                            $service->verifyDerivative($derivative, $record);
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('reprocess')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($record) {
                        ProcessMediaJob::dispatch($record);
                    })
                    ->requiresConfirmation(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, $record, MediaUsageService $usageService) {
                        if ($usageService->isInUse($record)) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete media')
                                ->body('This media is currently in use and cannot be deleted.')
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, MediaDeletionService $deletionService) {
                            $result = $deletionService->bulkDelete($records);

                            if (count($result['errors']) > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Bulk deletion aborted')
                                    ->body(count($result['errors']).' record(s) failed validation. No records were deleted.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->success()
                                    ->title('Deleted')
                                    ->body("{$result['deleted']} media record(s) deleted.")
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}

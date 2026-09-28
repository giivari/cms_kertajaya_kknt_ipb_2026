<?php

namespace App\Filament\Resources\Media\Tables;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Filament\Exports\MediaExporter;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Support\MediaThumbnail;
use App\Filament\Support\AdminTable;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Services\MediaDeletionService;
use App\Services\MediaUsageService;
use App\Services\WatermarkVerificationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-media-library')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('derivatives'))
            ->searchPlaceholder('Cari nama media...')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Pratinjau')
                    ->state(fn (Media $record): ?string => str_starts_with((string) $record->mime_type, 'image/') ? MediaThumbnail::url($record) : null)
                    ->defaultImageUrl(fn (Media $record): string => MediaThumbnail::placeholderUrl($record->mime_type))
                    ->extraImgAttributes(fn (Media $record): array => [
                        'class' => 'admin-media-thumbnail',
                        'alt' => 'Pratinjau '.$record->original_filename,
                    ])
                    ->square()
                    ->size(52),
                TextColumn::make('original_filename')
                    ->label('Nama Media')
                    ->description(fn (Media $record): string => self::mimeLabel($record->mime_type).' · '.Number::fileSize((int) $record->size, precision: 1))
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mime_type')
                    ->label('Jenis Berkas')
                    ->formatStateUsing(fn (string $state): string => self::mimeLabel($state))
                    ->badge()
                    ->visibleFrom('md')
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn ($state): string => Number::fileSize((int) $state, precision: 1))
                    ->visibleFrom('md')
                    ->sortable(),
                TextColumn::make('processing_status')
                    ->label('Status Ketersediaan')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match (is_object($state) ? $state->value : $state) {
                        'completed' => 'Selesai', 'failed' => 'Gagal', 'processing' => 'Sedang Diproses', default => 'Menunggu',
                    })
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : $state) {
                        'completed' => 'success', 'failed' => 'danger', 'processing' => 'info', default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('last_processing_status')
                    ->label('Pemrosesan Terakhir')
                    ->placeholder('Belum tercatat')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Berhasil', 'failed' => 'Gagal; dapat diproses ulang',
                        'processing' => 'Sedang diproses', default => $state,
                    })
                    ->wrap(),
                TextColumn::make('cleanup_status')
                    ->label('Pembersihan')
                    ->placeholder('Tidak dijadwalkan')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu penyelesaian',
                        'failed' => 'Gagal; dapat dicoba ulang',
                        'completed' => 'Selesai',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'failed' => 'danger', 'pending' => 'warning', 'completed' => 'success', default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invisible_watermark_status')
                    ->label('Status Tanda Air')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match (is_object($state) ? $state->value : $state) {
                        'applied' => 'Diterapkan', 'verified' => 'Terverifikasi', 'failed' => 'Gagal',
                        'processing' => 'Sedang Diproses', 'unsupported' => 'Tidak Didukung', default => 'Menunggu',
                    })
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : $state) {
                        'verified' => 'success', 'failed' => 'danger', 'processing' => 'info', 'unsupported' => 'gray', default => 'warning',
                    })
                    ->visibleFrom('lg')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diunggah pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diproses')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Status arsip'),
                SelectFilter::make('mime_type')
                    ->label('Jenis Berkas')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/webp' => 'WebP',
                        'application/pdf' => 'PDF',
                    ]),
                SelectFilter::make('processing_status')
                    ->label('Status Pemrosesan')
                    ->options([
                        MediaProcessingStatus::PENDING->value => 'Menunggu',
                        MediaProcessingStatus::PROCESSING->value => 'Sedang Diproses',
                        MediaProcessingStatus::COMPLETED->value => 'Selesai',
                        MediaProcessingStatus::FAILED->value => 'Gagal',
                    ]),
                SelectFilter::make('invisible_watermark_status')
                    ->label('Status Tanda Air')
                    ->options([
                        InvisibleWatermarkStatus::PENDING->value => 'Menunggu',
                        InvisibleWatermarkStatus::PROCESSING->value => 'Sedang Diproses',
                        InvisibleWatermarkStatus::APPLIED->value => 'Diterapkan',
                        InvisibleWatermarkStatus::VERIFIED->value => 'Terverifikasi',
                        InvisibleWatermarkStatus::FAILED->value => 'Gagal',
                        InvisibleWatermarkStatus::UNSUPPORTED->value => 'Tidak Didukung',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    Action::make('verify')
                        ->label('Verifikasi')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (Media $record): bool => str_starts_with((string) $record->mime_type, 'image/'))
                        ->action(function ($record, WatermarkVerificationService $service) {
                            $derivative = $record->derivatives()->where('derivative_type', 'public')->first();
                            if (! $derivative) {
                                Notification::make()
                                    ->warning()
                                    ->title('Derivative publik tidak tersedia')
                                    ->body('Media tidak dapat diverifikasi sebelum derivative publik yang aktif tersedia.')
                                    ->send();

                                return;
                            }

                            $result = $service->verifyAndPersistActiveDerivative($derivative, $record);
                            \App\Services\AuditLogService::log(
                                $result['verified'] ? 'media_verified' : 'media_verification_failed',
                                $record,
                            );
                            if ($result['verified']) {
                                Notification::make()
                                    ->success()
                                    ->title('Media terverifikasi')
                                    ->body('Autentisitas metadata, integritas byte, dan format derivative telah diperiksa.')
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title($result['legacy_unverifiable'] ? 'Media lama belum dapat diverifikasi' : 'Verifikasi media gagal')
                                ->body($result['legacy_unverifiable']
                                    ? 'Derivative ini belum memiliki checksum tepercaya untuk verifikasi byte final.'
                                    : 'Metadata, checksum, atau format derivative tidak memenuhi persyaratan verifikasi.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi Media')
                        ->modalDescription('Sistem akan memeriksa tanda air pada derivative publik yang tersedia.')
                        ->modalSubmitActionLabel('Verifikasi'),
                    Action::make('reprocess')
                        ->label('Proses Ulang')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($record) {
                            ProcessMediaJob::dispatchSync($record);
                            $record->refresh();
                            if ($record->last_processing_status === 'failed') {
                                Notification::make()->warning()->title('Pemrosesan belum berhasil')
                                    ->body('Derivative aktif sebelumnya dipertahankan bila tersedia.')->send();
                            } elseif ($record->last_processing_status === 'completed') {
                                Notification::make()->success()->title('Media berhasil diproses')->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Proses Ulang Media')
                        ->modalDescription('Media akan masuk kembali ke antrean pemrosesan dan verifikasi.')
                        ->modalSubmitActionLabel('Proses Ulang'),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Media')
                        ->modalDescription('Media hanya dapat dihapus bila tidak sedang diproses dan tidak digunakan oleh konten lain.')
                        ->modalSubmitActionLabel('Hapus')
                        ->using(fn ($record, MediaDeletionService $service): bool => $service->archive($record))
                        ->before(function (DeleteAction $action, $record, MediaUsageService $usageService) {
                            if ($usageService->isInUse($record)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Media tidak dapat dihapus')
                                    ->body('Media sedang digunakan dan tidak dapat dihapus.')
                                    ->send();
                                $action->cancel();
                            }
                        }),
                    RestoreAction::make()
                        ->label('Pulihkan')
                        ->using(fn ($record, MediaDeletionService $service): bool => $service->restore($record)),
                    ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->using(fn ($record, MediaDeletionService $service): bool => $service->permanentlyDelete($record)),
                ])
                    ->label('Aksi Media')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Media'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Hapus yang Dipilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, MediaDeletionService $deletionService) {
                            $result = $deletionService->bulkDelete($records);

                            if (count($result['errors']) > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Penghapusan dibatalkan')
                                    ->body(count($result['errors']).' media gagal divalidasi. Tidak ada media yang dihapus.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil dihapus')
                                    ->body("{$result['deleted']} media berhasil dihapus.")
                                    ->send();
                            }
                        }),
                ]),
                AdminTable::exportAction(MediaExporter::class, MediaResource::class),
            ])
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateHeading('Belum ada media')
            ->emptyStateDescription('Unggah gambar atau dokumen untuk digunakan pada konten website.');
    }

    private static function mimeLabel(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'Gambar JPEG',
            'image/png' => 'Gambar PNG',
            'image/webp' => 'Gambar WebP',
            'application/pdf' => 'Dokumen PDF',
            default => 'Berkas',
        };
    }
}

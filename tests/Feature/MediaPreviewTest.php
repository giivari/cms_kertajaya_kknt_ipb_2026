<?php

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Models\Admin;
use App\Models\Media;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('media preview UI stays disabled without creating or changing media', function () {
    $admin = Admin::factory()->create();
    $media = Media::factory()->create(['original_filename' => 'media-lama.jpg'])->refresh();
    $beforeCount = Media::count();
    $beforeState = [$media->original_filename, $media->filename, $media->processing_status, $media->invisible_watermark_status];
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateMedia::class)
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview);

    Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $media->getRouteKey()])
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview);

    $fresh = $media->fresh();

    expect(Media::count())->toBe($beforeCount)
        ->and([$fresh->original_filename, $fresh->filename, $fresh->processing_status, $fresh->invisible_watermark_status])->toBe($beforeState);
});

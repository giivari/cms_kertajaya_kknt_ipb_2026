<?php

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Models\Admin;
use App\Models\Document;
use App\Models\Media;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('document create and edit preview render without registering preview actions or persistence', function () {
    $admin = Admin::factory()->create();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');
    $beforeCreate = \App\Models\Document::count();

    Livewire::actingAs($admin)->test(CreateDocument::class)
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview)
        ->assertActionNotMounted();

    expect(\App\Models\Document::count())->toBe($beforeCreate);

    $media = Media::factory()->create();
    $document = Document::create([
        'title' => 'Dokumen Lama',
        'status' => 'draft',
        'file_media_id' => $media->id,
        'download_count' => 0,
    ]);
    
    $beforeEdit = \App\Models\Document::count();

    Livewire::actingAs($admin)->test(EditDocument::class, ['record' => $document->getRouteKey()])
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview)
        ->assertActionNotMounted();

    expect($document->fresh()->title)->toBe('Dokumen Lama')
        ->and(\App\Models\Document::count())->toBe($beforeEdit);
});

<?php

use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\News\Pages\EditNews;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\News;
use App\Models\PreviewToken;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('news create and edit pages render without registering preview actions or persistence', function () {
    $admin = Admin::factory()->create();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');
    $beforeCreate = [News::count(), AuditLog::count(), PreviewToken::count()];

    Livewire::actingAs($admin)->test(CreateNews::class)
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview)
        ->assertActionNotMounted();

    expect([News::count(), AuditLog::count(), PreviewToken::count()])->toBe($beforeCreate);

    $news = News::create(['title' => 'Berita Lama', 'content' => 'Isi lama', 'status' => 'published'])->refresh();
    $identity = [$news->title, $news->slug, $news->published_at?->format('Y-m-d H:i:s.u')];
    $beforeEdit = [News::count(), AuditLog::count(), PreviewToken::count()];

    Livewire::actingAs($admin)->test(EditNews::class, ['record' => $news->getRouteKey()])
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview)
        ->assertActionNotMounted();

    $news->refresh();
    expect([$news->title, $news->slug, $news->published_at?->format('Y-m-d H:i:s.u')])->toBe($identity)
        ->and([News::count(), AuditLog::count(), PreviewToken::count()])->toBe($beforeEdit);
});

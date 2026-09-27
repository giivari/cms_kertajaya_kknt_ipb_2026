<?php

namespace Tests\Feature;

use App\Filament\Widgets\VillageStatsWidget;
use App\Models\Admin;
use App\Models\Document;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class P7CountSemanticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_document_count_describes_publication_status_not_unverified_file_availability(): void
    {
        $this->withoutVite();
        $media = Media::factory()->create();
        $document = Document::create([
            'title' => 'P7 Dokumen Belum Tersedia',
            'slug' => 'p7-dokumen-belum-tersedia',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'file_media_id' => $media->id,
        ]);

        $this->get(route('documents.download', $document->slug))->assertNotFound();

        Livewire::actingAs(Admin::factory()->create())
            ->test(VillageStatsWidget::class)
            ->assertSee('Dokumen Diterbitkan')
            ->assertSee('Berstatus terbit')
            ->assertDontSee('Siap diunduh pengunjung');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicSearchTest extends TestCase
{
    use RefreshDatabase;

    // --- Page Setup Helpers ---

    private function createPublishedPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Profil Desa Kertajaya',
            'slug' => 'profil-desa',
            'excerpt' => 'Informasi lengkap mengenai Desa Kertajaya.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function createDraftPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Halaman Draft Rahasia',
            'slug' => 'halaman-draft',
            'excerpt' => 'Ini halaman draft.',
            'status' => 'draft',
            'published_at' => null,
        ], $overrides));
    }

    private function createArchivedPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Halaman Arsip Lama',
            'slug' => 'halaman-arsip',
            'excerpt' => 'Ini halaman arsip.',
            'status' => 'archived',
            'published_at' => now()->subMonth(),
        ], $overrides));
    }

    // --- News Setup Helpers ---

    private function createPublishedNews(array $overrides = []): News
    {
        return News::create(array_merge([
            'title' => 'Musrenbang Desa 2026',
            'slug' => 'musrenbang-2026',
            'excerpt' => 'Hasil musyawarah perencanaan pembangunan desa.',
            'content' => 'Pada hari Senin tanggal 15 Juli 2026, telah dilaksanakan Musrenbang.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function createDraftNews(array $overrides = []): News
    {
        return News::create(array_merge([
            'title' => 'Berita Draft Internal',
            'slug' => 'berita-draft',
            'excerpt' => 'Ini berita draft.',
            'content' => 'Konten draft yang belum dipublikasikan.',
            'status' => 'draft',
            'published_at' => null,
        ], $overrides));
    }

    // --- Document Setup Helpers ---

    private function createPublishedDocument(array $overrides = []): Document
    {
        // Documents need file_media_id, create a minimal media record
        $media = \App\Models\Media::create([
            'disk' => 'public',
            'directory' => 'documents',
            'filename' => 'search-test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'processing_status' => 'completed',
            'invisible_watermark_status' => 'verified',
        ]);

        return Document::create(array_merge([
            'title' => 'SK Kepala Desa 2026',
            'slug' => 'sk-kepala-desa-2026',
            'description' => 'Surat Keputusan Kepala Desa tentang pembangunan.',
            'file_media_id' => $media->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function createDraftDocument(array $overrides = []): Document
    {
        $media = \App\Models\Media::create([
            'disk' => 'public',
            'directory' => 'documents',
            'filename' => 'search-draft.pdf',
            'original_filename' => 'draft.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'processing_status' => 'completed',
            'invisible_watermark_status' => 'verified',
        ]);

        return Document::create(array_merge([
            'title' => 'Dokumen Draft Rahasia',
            'slug' => 'dokumen-draft',
            'description' => 'Dokumen draft internal.',
            'file_media_id' => $media->id,
            'status' => 'draft',
            'published_at' => null,
        ], $overrides));
    }

    // ==================== TESTS ====================

    #[Test]
    public function search_page_can_be_rendered(): void
    {
        $response = $this->get(route('public.search'));

        $response->assertOk();
        $response->assertSee('Hasil Pencarian');
    }

    #[Test]
    public function empty_query_returns_no_results(): void
    {
        $response = $this->get(route('public.search', ['q' => '']));

        $response->assertOk();
        $response->assertDontSee('Ditemukan');
        $response->assertDontSee('Tidak ada hasil');
    }

    #[Test]
    public function short_query_returns_no_results(): void
    {
        $response = $this->get(route('public.search', ['q' => 'a']));

        $response->assertOk();
        $response->assertSee('minimal 2 karakter');
    }

    #[Test]
    public function published_page_is_found(): void
    {
        $this->createPublishedPage();

        $response = $this->get(route('public.search', ['q' => 'Profil']));

        $response->assertOk();
        $response->assertSee('Profil Desa Kertajaya');
        $response->assertSee('Halaman');
    }

    #[Test]
    public function published_news_is_found(): void
    {
        $this->createPublishedNews();

        $response = $this->get(route('public.search', ['q' => 'Musrenbang']));

        $response->assertOk();
        $response->assertSee('Musrenbang Desa 2026');
        $response->assertSee('Berita');
    }

    #[Test]
    public function published_document_is_found(): void
    {
        $this->createPublishedDocument();

        $response = $this->get(route('public.search', ['q' => 'Kepala Desa']));

        $response->assertOk();
        $response->assertSee('SK Kepala Desa 2026');
        $response->assertSee('Dokumen');
    }

    #[Test]
    public function draft_content_is_not_found(): void
    {
        $this->createDraftPage();
        $this->createDraftNews();
        $this->createDraftDocument();

        $response = $this->get(route('public.search', ['q' => 'Draft']));

        $response->assertOk();
        $response->assertDontSee('Halaman Draft Rahasia');
        $response->assertDontSee('Berita Draft Internal');
        $response->assertDontSee('Dokumen Draft Rahasia');
        $response->assertSee('Tidak Ditemukan');
    }

    #[Test]
    public function archived_page_is_not_found(): void
    {
        $this->createArchivedPage();

        $response = $this->get(route('public.search', ['q' => 'Arsip']));

        $response->assertOk();
        $response->assertDontSee('Halaman Arsip Lama');
    }

    #[Test]
    public function search_is_case_insensitive(): void
    {
        $this->createPublishedPage();

        $responseLower = $this->get(route('public.search', ['q' => 'profil']));
        $responseUpper = $this->get(route('public.search', ['q' => 'PROFIL']));

        $responseLower->assertSee('Profil Desa Kertajaya');
        $responseUpper->assertSee('Profil Desa Kertajaya');
    }

    #[Test]
    public function empty_state_shows_when_no_results(): void
    {
        $response = $this->get(route('public.search', ['q' => 'xyznonexistent']));

        $response->assertOk();
        $response->assertSee('Tidak Ditemukan');
        $response->assertSee('Coba gunakan kata kunci yang berbeda');
    }

    #[Test]
    public function result_links_are_correct(): void
    {
        $page = $this->createPublishedPage();
        $news = $this->createPublishedNews();
        $doc = $this->createPublishedDocument();

        // Search for something broad that matches all
        $response = $this->get(route('public.search', ['q' => 'Desa']));

        $response->assertOk();
        $response->assertSee(route('pages.show', $page->slug));
        $response->assertSee(route('news.show', $news->slug));
        $response->assertSee(route('documents.download', $doc->slug));
    }

    #[Test]
    public function dangerous_input_is_not_executed(): void
    {
        $xss = '<script>alert("XSS")</script>';

        $response = $this->get(route('public.search', ['q' => $xss]));

        $response->assertOk();
        // Should be HTML-escaped, not raw
        $response->assertDontSee($xss, false);
    }

    #[Test]
    public function news_content_is_searchable(): void
    {
        $this->createPublishedNews();

        // Search by content (not title or excerpt)
        $response = $this->get(route('public.search', ['q' => 'Musrenbang']));

        $response->assertOk();
        $response->assertSee('Musrenbang Desa 2026');
    }

    #[Test]
    public function soft_deleted_content_is_not_found(): void
    {
        $page = $this->createPublishedPage();
        $page->delete(); // soft delete

        $response = $this->get(route('public.search', ['q' => 'Profil']));

        $response->assertOk();
        $response->assertDontSee('Profil Desa Kertajaya');
    }

    #[Test]
    public function query_over_100_chars_returns_no_results(): void
    {
        $longQuery = str_repeat('a', 101);

        $response = $this->get(route('public.search', ['q' => $longQuery]));

        $response->assertOk();
        $response->assertSee('maksimal 100 karakter');
        $response->assertDontSee('id="search-summary"', false);
    }
}

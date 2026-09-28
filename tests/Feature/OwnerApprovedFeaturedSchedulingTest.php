<?php

namespace Tests\Feature;

use App\Filament\Resources\News\Pages\CreateNews;
use App\Enums\PageStatus;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use App\Models\News;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class OwnerApprovedFeaturedSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_consumes_only_eligible_featured_pages_and_prioritizes_featured_albums(): void
    {
        Page::create(['title' => 'PAGE-FEATURED-ONE', 'status' => 'published', 'is_featured' => true]);
        Page::create(['title' => 'PAGE-NORMAL-TWO', 'status' => 'published', 'is_featured' => false]);
        Page::create(['title' => 'PAGE-FUTURE-THREE', 'status' => 'published', 'is_featured' => true,
            'published_at' => now()->addDay()]);
        $archived = Page::create(['title' => 'PAGE-ARCHIVED-FOUR', 'status' => 'published', 'is_featured' => true]);
        $archived->delete();

        GalleryAlbum::create(['title' => 'ALBUM-NORMAL-ONE', 'status' => 'published', 'is_featured' => false]);
        GalleryAlbum::create(['title' => 'ALBUM-FEATURED-TWO', 'status' => 'published', 'is_featured' => true]);
        GalleryAlbum::create(['title' => 'ALBUM-FUTURE-THREE', 'status' => 'published', 'is_featured' => true,
            'published_at' => now()->addDay()]);

        $this->get('/')->assertOk()
            ->assertSee('PAGE-FEATURED-ONE')
            ->assertDontSee('PAGE-NORMAL-TWO')
            ->assertDontSee('PAGE-FUTURE-THREE')
            ->assertDontSee('PAGE-ARCHIVED-FOUR')
            ->assertSeeInOrder(['ALBUM-FEATURED-TWO', 'ALBUM-NORMAL-ONE'])
            ->assertDontSee('ALBUM-FUTURE-THREE');
    }

    public function test_deliberate_future_publication_survives_status_transition_and_appears_only_when_due(): void
    {
        $future = now()->addDay()->startOfMinute();
        $page = Page::create(['title' => 'SCHEDULED-PAGE', 'status' => PageStatus::DRAFT,
            'is_featured' => true, 'published_at' => $future]);
        $page->update(['status' => PageStatus::PUBLISHED]);
        $this->assertTrue($page->fresh()->published_at->equalTo($future),
            'Expected '.$future->toIso8601String().' got '.$page->fresh()->published_at?->toIso8601String());
        $this->get(route('pages.show', $page->slug))->assertNotFound();
        $this->get('/')->assertDontSee('SCHEDULED-PAGE');

        $album = GalleryAlbum::create(['title' => 'SCHEDULED-ALBUM', 'status' => 'draft',
            'is_featured' => true, 'published_at' => $future]);
        $album->update(['status' => 'published']);
        $this->assertTrue($album->fresh()->published_at->equalTo($future));

        $news = News::create(['title' => 'SCHEDULED-NEWS', 'content' => 'body',
            'status' => 'draft', 'published_at' => $future]);
        $news->update(['status' => 'published']);
        $this->assertTrue($news->fresh()->published_at->equalTo($future));

        $this->travelTo($future->copy()->addMinute());
        $this->get(route('pages.show', $page->slug))->assertOk();
        $this->get('/')->assertSee('SCHEDULED-PAGE')->assertSee('SCHEDULED-ALBUM');
    }

    public function test_admin_news_form_persists_a_future_schedule_without_publishing_early(): void
    {
        $admin = Admin::factory()->create();
        $futureLocal = now('Asia/Jakarta')->addDays(2)->startOfMinute();

        Livewire::actingAs($admin)->test(CreateNews::class)
            ->fillForm([
                'title' => 'FORM-SCHEDULED-NEWS',
                'content' => '<p>Isi jadwal.</p>',
                'published_at' => $futureLocal->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $news = News::query()->where('title', 'FORM-SCHEDULED-NEWS')->firstOrFail();
        $this->assertTrue($news->published_at->isFuture());
        $news->update(['status' => 'published']);
        $this->assertTrue($news->fresh()->published_at->isFuture());
        $this->get(route('news.show', $news->slug))->assertNotFound();
    }

    public function test_featured_cards_have_stable_bounded_order_and_no_original_fallback(): void
    {
        foreach (range(1, 4) as $number) {
            Page::create([
                'title' => "BOUND-PAGE-{$number}",
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subMinutes(5 - $number),
            ]);
        }
        foreach (range(1, 5) as $number) {
            GalleryAlbum::create([
                'title' => "BOUND-ALBUM-{$number}",
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subMinutes(6 - $number),
            ]);
        }

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['BOUND-PAGE-4', 'BOUND-PAGE-3', 'BOUND-PAGE-2'])
            ->assertDontSee('BOUND-PAGE-1')
            ->assertSeeInOrder(['BOUND-ALBUM-5', 'BOUND-ALBUM-4'])
            ->assertSee(route('gallery.show', GalleryAlbum::where('title', 'BOUND-ALBUM-3')->firstOrFail()->slug))
            ->assertDontSee('BOUND-ALBUM-3')
            ->assertDontSee('BOUND-ALBUM-2')
            ->assertDontSee('BOUND-ALBUM-1')
            ->assertDontSee('/storage/originals/');
    }
}

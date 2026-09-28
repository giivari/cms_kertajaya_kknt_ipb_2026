<?php

namespace Tests\Feature;

use App\Filament\Support\PreviewStateNormalizer;
use App\Models\Admin;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\GalleryAlbums\Pages\CreateGalleryAlbum;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditFooterMenu;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Pages\WebsiteSettings;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewDraftStore;
use App\Services\Preview\PreviewTemporaryAssets;
use App\Services\Preview\PreviewTokenStore;
use App\Services\SettingsService;
use App\Support\Preview\PreviewContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class P4BPreviewIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('tmp-for-tests');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($this->admin);
        $this->startSession();
        $session = app('session.store');
        $session->put('session_created_at', time());
        $session->save();
        $this->sessionId = $session->getId();
        $this->withCookie($session->getName(), $this->sessionId);
    }

    public function test_menu_preview_preserves_destinations_hierarchy_and_business_rows(): void
    {
        $state = PreviewStateNormalizer::normalize('menu', [
            'location' => Menu::FOOTER,
            'items' => [[
                'label' => 'Tautan Luar', 'link_type' => 'custom',
                'custom_url' => 'https://example.test/desa', 'target' => '_blank',
                'children' => [[
                    'label' => 'Beranda Anak', 'link_type' => 'home', 'is_visible' => true,
                ]],
            ], [
                'label' => 'Tidak Aman', 'link_type' => 'custom',
                'custom_url' => 'javascript:alert(1)',
            ]],
        ]);

        $this->assertSame('https://example.test/desa', $state['items'][0]['custom_url']);
        $this->assertSame('home', $state['items'][0]['children'][0]['link_type']);
        $this->assertSame('#', $state['items'][1]['custom_url']);

        $token = $this->token('menu', $state);
        $this->get(route('admin.preview.show', $token))->assertOk()
            ->assertSee('Tautan Luar')->assertSee('Beranda Anak')
            ->assertSee('https://example.test/desa')->assertDontSee('javascript:');
        $this->assertSame(0, Menu::count());
        $this->assertSame(0, MenuItem::count());
    }

    public function test_active_create_editor_actions_issue_tokens_without_creating_business_records(): void
    {
        config(['preview.ui_enabled' => true]);
        foreach ([
            [CreateNews::class, ['title' => 'Berita Belum Disimpan']],
            [CreatePage::class, ['title' => 'Halaman Belum Disimpan']],
            [CreateGalleryAlbum::class, ['title' => 'Album Belum Disimpan']],
            [CreateDocument::class, ['title' => 'Dokumen Belum Disimpan']],
            [CreateMenu::class, ['location' => Menu::HEADER]],
        ] as [$editor, $state]) {
            $before = PreviewToken::count();
            Livewire::test($editor)->fillForm($state)
                ->call('mountAction', 'preview', [], ['schemaComponent' => 'content.form-actions'])
                ->call('callMountedAction');
            $this->assertSame($before + 1, PreviewToken::count(), $editor);
        }
        $this->assertSame(0, \App\Models\News::count());
        $this->assertSame(0, Page::count());
        $this->assertSame(0, \App\Models\GalleryAlbum::count());
        $this->assertSame(0, \App\Models\Document::count());
        $this->assertSame(0, Menu::count());
    }

    public function test_footer_menu_edit_preview_keeps_its_location_without_mutating_menu(): void
    {
        config(['preview.ui_enabled' => true]);
        $menu = Menu::create(['location' => Menu::FOOTER]);

        Livewire::test(EditFooterMenu::class, ['record' => $menu->id])
            ->call('mountAction', 'preview', [], ['schemaComponent' => 'content.form-actions'])
            ->call('callMountedAction');

        $this->assertSame(1, PreviewToken::count());
        $payload = json_decode(Crypt::decryptString(PreviewToken::firstOrFail()->encrypted_payload), true);
        $this->assertSame(Menu::FOOTER, $payload['state']['location']);
        $this->assertSame(Menu::FOOTER, $menu->fresh()->location);
        $this->assertSame(0, MenuItem::count());
    }

    public function test_website_settings_action_creates_preview_without_writing_settings(): void
    {
        config(['preview.ui_enabled' => true]);
        $before = \App\Models\WebsiteSetting::count();
        Livewire::test(WebsiteSettings::class)->fillForm(['village_name' => 'Nama Belum Disimpan'])
            ->call('mountAction', 'preview')
            ->call('callMountedAction');
        $this->assertSame(1, PreviewToken::count());
        $this->assertSame($before, \App\Models\WebsiteSetting::count());
    }

    public function test_media_create_editor_exposes_the_token_preview_action_without_uploading_a_file(): void
    {
        config(['preview.ui_enabled' => true]);
        Livewire::test(CreateMedia::class)->assertActionVisible(
            \Filament\Actions\Testing\TestAction::make('preview')->schemaComponent('form-actions', schema: 'content'),
        );
        $this->assertSame(0, \App\Models\Media::count());
    }

    public function test_settings_preview_uses_unsaved_overlay_without_persisting_or_leaking_it(): void
    {
        SettingsService::setMany(['village_name' => 'Nama Tersimpan', 'hero_title' => 'Hero Tersimpan']);
        $state = PreviewStateNormalizer::normalize('settings', [
            'village_name' => 'Nama Pratinjau', 'hero_title' => 'Hero Pratinjau',
            'hero_button_1_custom_url' => 'javascript:alert(1)',
            'watermark_opacity' => 45, 'profil_bg_opacity' => '80',
            'service_hours' => [['day' => 'Senin', 'time' => '08.00–15.00']],
        ]);
        $this->assertSame(45, $state['watermark_opacity']);
        $this->assertSame('Senin', $state['service_hours'][0]['day']);
        $this->assertSame('#', $state['hero_button_1_custom_url']);

        $token = $this->token('settings', $state, 'edit');
        $this->get(route('admin.preview.show', $token))->assertOk()
            ->assertSee('Nama Pratinjau')->assertSee('Hero Pratinjau');
        $this->assertFalse(app()->bound(PreviewContext::class));
        $this->assertSame('Nama Tersimpan', SettingsService::get('village_name'));
        $this->assertSame('Hero Tersimpan', SettingsService::get('hero_title'));
        $this->get('/')->assertOk()->assertDontSee('Nama Pratinjau');
    }

    public function test_page_builder_preview_preserves_unsaved_identity_layout_visibility_and_document_namespace(): void
    {
        $state = PreviewStateNormalizer::normalize('page', [
            'title' => 'Halaman Belum Disimpan',
            'builder_sections' => [[
                'id' => 71, 'name' => 'Bagian Utama', 'layout_type' => 'two_columns',
                'section_settings' => ['accent' => 'green'], 'is_visible' => true,
                'components' => [[
                    'type' => 'heading', 'data' => ['id' => 81, 'text' => 'Judul Pratinjau',
                        'is_visible' => true, 'component_settings' => ['custom' => 'kept']],
                ], [
                    'type' => 'documents', 'data' => ['id' => 82, 'document_ids' => [], 'documents' => [991]],
                ]],
            ]],
        ]);
        $this->assertSame(71, $state['builder_sections'][0]['id']);
        $this->assertSame(81, $state['builder_sections'][0]['components'][0]['data']['id']);
        $this->assertSame([991], $state['builder_sections'][0]['components'][1]['data']['documents']);

        $token = $this->token('page', $state);
        $this->get(route('admin.preview.show', $token))->assertOk()
            ->assertSee('Halaman Belum Disimpan')->assertSee('Judul Pratinjau');
        $this->assertSame(0, Page::count());
        $this->assertSame(0, PageSection::count());
        $this->assertSame(0, PageComponent::count());
    }

    public function test_private_temporary_image_is_bound_to_preview_owner_session_and_bytes(): void
    {
        $image = imagecreatetruecolor(2, 2);
        imagefilledrectangle($image, 0, 0, 1, 1, imagecolorallocate($image, 20, 100, 50));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $uploadName = 'editor-meta'.base64_encode('editor.png').'-.png';
        Storage::disk('tmp-for-tests')->put('livewire-tmp/'.$uploadName, $bytes);
        $upload = new TemporaryUploadedFile($uploadName, 'tmp-for-tests');
        $assets = new PreviewTemporaryAssets();
        $state = PreviewStateNormalizer::normalize('media', $assets->capture([
            'file' => [$upload], 'original_filename' => 'editor.png',
        ]));
        $assetId = $state['file_asset_id'];
        $this->assertNotNull($assetId);
        $token = $this->token('media', $state, assets: $assets->map());
        $url = route('admin.preview.asset', ['token' => $token, 'assetToken' => $assetId]);

        $this->get(route('admin.preview.show', $token))->assertOk()->assertSee($url);
        $response = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
        $this->get(route('admin.preview.asset', ['token' => $token, 'assetToken' => str_repeat('a', 32)]))->assertNotFound();

        $otherImage = imagecreatetruecolor(2, 2);
        imagefilledrectangle($otherImage, 0, 0, 1, 1, imagecolorallocate($otherImage, 200, 10, 40));
        ob_start();
        imagepng($otherImage);
        $otherBytes = (string) ob_get_clean();
        imagedestroy($otherImage);
        Storage::disk('local')->put($assets->map()[$assetId]['path'], $otherBytes);
        $this->get($url)->assertNotFound();
        app(PreviewTokenStore::class)->revoke($token, $this->admin->id, $this->sessionId);
        $this->get($url)->assertNotFound();
    }

    public function test_corruption_expiry_and_deterministic_quota_fail_closed(): void
    {
        config(['preview.max_active' => 2]);
        $store = app(PreviewTokenStore::class);
        $otherSession = $store->create($this->admin->id, 'another-session', 'news', ['type' => 'news']);
        $first = $this->token('news', ['title' => 'Satu']);
        $second = $this->token('news', ['title' => 'Dua']);
        $third = $this->token('news', ['title' => 'Tiga']);

        $this->assertNull($store->retrieve($first, $this->admin->id, $this->sessionId));
        $this->assertNotNull($store->retrieve($second, $this->admin->id, $this->sessionId));
        $this->assertNotNull($store->retrieve($third, $this->admin->id, $this->sessionId));
        $this->assertNotNull($store->retrieve($otherSession, $this->admin->id, 'another-session'));

        $orphan = 'preview-assets/'.str_repeat('a', 32).'/'.str_repeat('b', 32);
        Storage::disk('local')->put($orphan, 'orphan fixture');
        touch(Storage::disk('local')->path($orphan), now()->subHours(3)->getTimestamp());
        $store->pruneExpired();
        Storage::disk('local')->assertMissing($orphan);

        PreviewToken::where('token_hash', hash('sha256', $second))->update(['encrypted_payload' => 'corrupt']);
        $this->get(route('admin.preview.show', $second))->assertNotFound();
        PreviewToken::where('token_hash', hash('sha256', $third))->update(['expires_at' => now()->subSecond()]);
        $this->get(route('admin.preview.show', $third))->assertNotFound();
        $this->assertSame(1, $store->pruneExpired());
        $this->assertNotNull($store->retrieve($otherSession, $this->admin->id, 'another-session'));
    }

    public function test_recovery_is_bounded_and_removes_stale_upload_objects(): void
    {
        $drafts = app(PreviewDraftStore::class);
        Storage::disk('tmp-for-tests')->put('livewire-tmp/recovery.png', 'not-public');
        $upload = new TemporaryUploadedFile('recovery.png', 'tmp-for-tests');
        $drafts->remember('editor', 5, ['title' => 'Belum Disimpan', 'file' => $upload]);
        $this->assertSame(['title' => 'Belum Disimpan', 'file' => null], $drafts->restore('editor', 5));
        $drafts->remember('editor', 5, ['title' => 'Aman', 'nested' => ['password' => 'rahasia', 'label' => 'Tetap']]);
        $this->assertSame(['title' => 'Aman', 'nested' => ['label' => 'Tetap']], $drafts->restore('editor', 5));
        $this->assertNull($drafts->restore('editor', 6));
        $drafts->forget('editor', 5);
        $this->assertNull($drafts->restore('editor', 5));
        $drafts->remember('editor', 5, ['title' => 'Kedaluwarsa']);
        $this->travel(31)->minutes();
        $this->assertNull($drafts->restore('editor', 5));
        $this->travelBack();
    }

    private function token(string $type, array $state, string $mode = 'create', array $assets = []): string
    {
        return app(PreviewTokenStore::class)->create($this->admin->id, $this->sessionId, $type, [
            'version' => 1, 'type' => $type, 'mode' => $mode,
            'record_id' => null, 'state' => $state, 'snapshot' => null,
            'temporary_assets_map' => $assets,
        ]);
    }
}

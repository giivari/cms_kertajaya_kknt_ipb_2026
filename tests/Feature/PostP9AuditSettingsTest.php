<?php

namespace Tests\Feature;

use App\Filament\Pages\WebsiteSettings;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Support\PreviewStateNormalizer;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Page;
use App\Services\MediaDeletionService;
use App\Services\SettingsService;
use App\Support\Preview\PreviewContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class PostP9AuditSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    public function test_social_settings_save_reload_footer_safety_and_unsaved_preview(): void
    {
        $this->admin();
        Livewire::test(WebsiteSettings::class)->fillForm([
            'social_twitter' => 'https://x.com/desa',
            'social_youtube' => 'https://youtube.com/@desa',
        ])->call('save')->assertHasNoFormErrors();

        $this->assertSame('https://x.com/desa', SettingsService::get('social_twitter'));
        $this->assertSame('https://youtube.com/@desa', SettingsService::get('social_youtube'));
        $this->get('/')->assertOk()->assertSee('href="https://x.com/desa"', false)
            ->assertSee('href="https://youtube.com/@desa"', false)
            ->assertSee('Facebook')->assertSee('Instagram');

        app()->instance(PreviewContext::class, new PreviewContext(
            'settings', PreviewStateNormalizer::normalize('settings', [
                'social_twitter' => 'https://x.com/unsaved',
                'social_youtube' => 'https://youtube.com/@unsaved',
            ]), null, 'edit', [],
        ));
        try {
            $this->get('/')->assertOk()->assertSee('href="https://x.com/unsaved"', false)
                ->assertSee('href="https://youtube.com/@unsaved"', false);
        } finally {
            app()->forgetInstance(PreviewContext::class);
        }
        $this->assertSame('https://x.com/desa', SettingsService::get('social_twitter'));

        SettingsService::setMany(['social_twitter' => '', 'social_youtube' => 'javascript:alert(1)']);
        $this->get('/')->assertOk()->assertDontSee('aria-label="Twitter/X"', false)
            ->assertDontSee('aria-label="YouTube"', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_menu_editor_records_one_outcome_per_create_and_reorder(): void
    {
        $admin = $this->admin();
        Livewire::test(CreateMenu::class)->fillForm([
            'location' => Menu::HEADER,
            'items' => [[
                'label' => 'Beranda', 'link_type' => 'home', 'is_visible' => true,
            ]],
        ])->call('create')->assertHasNoFormErrors();
        $menu = Menu::where('location', Menu::HEADER)->sole();
        $this->assertSame(1, AuditLog::where('admin_id', $admin->id)->where('subject_type', Menu::class)
            ->where('subject_id', $menu->id)->where('event_type', 'menu_created')->count());

        $editor = Livewire::test(EditMenu::class, ['record' => $menu->id]);
        $items = $editor->get('data.items');
        $items[array_key_first($items)]['label'] = 'Beranda Desa';
        $editor->fillForm(['items' => $items])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Beranda Desa', $menu->items()->firstOrFail()->label);
        $this->assertSame(1, AuditLog::where('admin_id', $admin->id)->where('subject_type', Menu::class)
            ->where('subject_id', $menu->id)->where('event_type', 'menu_updated')->count());
        $this->assertSame(0, AuditLog::where('subject_type', \App\Models\MenuItem::class)->count());
        $menu->delete();
        $this->assertSame(1, AuditLog::where('subject_id', $menu->id)->where('event_type', 'menu_deleted')->count());
    }

    public function test_media_archive_and_denied_delete_do_not_claim_false_success(): void
    {
        $admin = $this->admin();
        $media = Media::create([
            'original_filename' => 'post-p9.png', 'filename' => 'post-p9.png',
            'disk' => 'local', 'directory' => 'originals', 'mime_type' => 'image/png',
            'extension' => 'png', 'size' => 1, 'processing_status' => 'completed',
        ]);
        $page = Page::create(['title' => 'References post P9 media', 'featured_media_id' => $media->id]);

        try {
            app(MediaDeletionService::class)->archive($media);
            $this->fail('Referenced media must not be archived.');
        } catch (\Exception $exception) {
            $this->assertFalse($media->fresh()->trashed());
            $this->assertSame(0, AuditLog::where('subject_type', Media::class)
                ->where('event_type', 'media_archived')->count());
        }

        $page->update(['featured_media_id' => null]);
        $this->assertTrue(app(MediaDeletionService::class)->archive($media));
        $this->assertSame(1, AuditLog::where('admin_id', $admin->id)->where('subject_type', Media::class)
            ->where('subject_id', $media->id)->where('event_type', 'media_archived')->count());
        $this->assertStringNotContainsString('post-p9.png', AuditLog::where('event_type', 'media_archived')->firstOrFail()->toJson());
        $this->assertTrue(app(MediaDeletionService::class)->permanentlyDelete($media));
        $this->assertSame(1, AuditLog::where('subject_id', $media->id)
            ->where('event_type', 'media_permanently_deleted')->count());
    }

    public function test_auth_success_failure_logout_are_recorded_without_credentials(): void
    {
        $admin = Admin::factory()->create(['password' => 'post-p9-private-password']);
        $this->assertFalse(Auth::guard('web')->attempt(['username' => $admin->username, 'password' => 'wrong-secret']));
        $this->assertSame(1, AuditLog::where('event_type', 'admin_login_failed')->count());

        $this->assertTrue(Auth::guard('web')->attempt(['username' => $admin->username, 'password' => 'post-p9-private-password']));
        $this->assertSame(1, AuditLog::where('event_type', 'admin_login')->count());
        Auth::guard('web')->logout();
        $this->assertSame(1, AuditLog::where('event_type', 'admin_logout')->count());
        $this->assertStringNotContainsString('wrong-secret', AuditLog::all()->toJson());
        $this->assertStringNotContainsString('post-p9-private-password', AuditLog::all()->toJson());
    }
}

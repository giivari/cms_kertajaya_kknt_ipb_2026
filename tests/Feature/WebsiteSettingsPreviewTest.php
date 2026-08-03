<?php

use App\Filament\Pages\WebsiteSettings;
use App\Models\Admin;
use App\Models\WebsiteSetting;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => config(['preview.ui_enabled' => true]));

test('website settings preview renders current state without updating settings', function () {
    $admin = Admin::factory()->create();
    $before = WebsiteSetting::query()->pluck('value', 'key')->all();

    $lw = Livewire::actingAs($admin)->test(WebsiteSettings::class)
        ->fillForm([
            'village_name' => 'Desa Pratinjau',
            'village_description' => '<script>Deskripsi belum disimpan</script>',
            'footer_copyright_text' => 'Hak cipta pratinjau',
            'social_facebook' => 'javascript:alert(1)',
        ])
        ->callAction('preview');

    $lw->assertRedirectContains('/preview-shell/');

    // Get the session ID AFTER Livewire requests
    $this->startSession();
    $session = app('session.store');
    $currentSessionId = $session->getId();
    $session->save();
    $this->withCookie($session->getName(), $currentSessionId);

    $store = app(PreviewTokenStore::class);
    $payload = [
        'version' => 1,
        'type' => 'settings',
        'mode' => 'edit',
        'record_id' => null,
        'state' => \App\Filament\Support\PreviewStateNormalizer::normalize('settings', [
            'village_name' => 'Desa Pratinjau',
            'village_description' => '<script>Deskripsi belum disimpan</script>',
            'footer_copyright_text' => 'Hak cipta pratinjau',
            'social_facebook' => 'javascript:alert(1)',
        ]),
        'snapshot' => null,
    ];
    $token = $store->create($admin->id, $currentSessionId, 'settings', $payload);

    $response = $this->actingAs($admin)->get(route('admin.preview.show', $token));
    
    $response->assertSee('Desa Pratinjau')
             ->assertSee('Hak cipta pratinjau')
             ->assertSee('&lt;script&gt;Deskripsi belum disimpan&lt;/script&gt;', false)
             ->assertDontSeeHtml('<script>')
             ->assertDontSeeHtml('href="javascript:');

    expect(WebsiteSetting::query()->pluck('value', 'key')->all())->toBe($before);
});

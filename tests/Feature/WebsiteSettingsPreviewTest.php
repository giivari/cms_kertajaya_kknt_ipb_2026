<?php

use App\Filament\Pages\WebsiteSettings;
use App\Models\Admin;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('website settings preview renders current state without updating settings', function () {
    $admin = Admin::factory()->create();
    $before = WebsiteSetting::query()->pluck('value', 'key')->all();

    Livewire::actingAs($admin)->test(WebsiteSettings::class)
        ->fillForm([
            'village_name' => 'Desa Pratinjau',
            'village_description' => '<script>Deskripsi belum disimpan</script>',
            'footer_copyright_text' => 'Hak cipta pratinjau',
            'social_facebook' => 'javascript:alert(1)',
        ])
        ->mountAction('preview')
        ->assertMountedActionModalSee('Desa Pratinjau')
        ->assertMountedActionModalSee('Hak cipta pratinjau')
        ->assertMountedActionModalSee('<script>Deskripsi belum disimpan</script>')
        ->assertMountedActionModalDontSeeHtml('<script>')
        ->assertMountedActionModalDontSeeHtml('href="javascript:');

    expect(WebsiteSetting::query()->pluck('value', 'key')->all())->toBe($before);
});

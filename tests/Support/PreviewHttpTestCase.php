<?php

namespace Tests\Support;

use App\Filament\Support\PreviewStateNormalizer;
use App\Models\Admin;
use App\Services\Preview\PreviewTokenStore;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class PreviewHttpTestCase extends TestCase
{
    use RefreshDatabase;

    protected Admin $previewAdmin;

    protected string $previewSession;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->previewAdmin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($this->previewAdmin);
        $this->startSession();
        $session = app('session.store');
        $session->put('session_created_at', time());
        $session->save();
        $this->previewSession = $session->getId();
        $this->withCookie($session->getName(), $this->previewSession);
    }

    protected function preview(string $type, array $rawState): \Illuminate\Testing\TestResponse
    {
        $token = app(PreviewTokenStore::class)->create($this->previewAdmin->id, $this->previewSession, $type, [
            'version' => 1, 'type' => $type, 'mode' => 'create', 'record_id' => null,
            'state' => PreviewStateNormalizer::normalize($type, $rawState), 'snapshot' => null,
        ]);

        return $this->get(route('admin.preview.show', $token));
    }
}

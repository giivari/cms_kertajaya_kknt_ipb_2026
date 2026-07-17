<?php

namespace Tests\Feature;

use App\Http\Middleware\ForcePasswordChange;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class ForcePasswordChangeLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_crafted_livewire_page_mutation_is_rejected_by_middleware()
    {
        $admin = Admin::factory()->create([
            'force_password_change' => true,
        ]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        // Fake the Livewire update payload
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'app.filament.resources.pages.pages.create-page']]),
                ],
            ],
        ]);
        // Tell Laravel it's a Livewire request
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_crafted_livewire_settings_mutation_is_rejected_by_middleware()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'app.filament.pages.website-settings']]),
                ],
            ],
        ]);
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_crafted_livewire_media_action_is_rejected_by_middleware()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'app.filament.resources.media.pages.list-media']]),
                ],
            ],
        ]);
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_crafted_livewire_core_payload_is_rejected_by_middleware()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'filament.core.notifications']]),
                ],
            ],
        ]);
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_crafted_livewire_unrelated_auth_payload_is_rejected_by_middleware()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'app.filament.pages.auth.login']]),
                ],
            ],
        ]);
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_approved_password_change_component_is_allowed_by_middleware()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $request = Request::create('/livewire/update', 'POST');
        $request->merge([
            'components' => [
                [
                    'snapshot' => json_encode(['memo' => ['name' => 'app.filament.pages.auth.edit-profile']]),
                ],
            ],
        ]);
        $request->headers->set('X-Livewire', 'true');
        app()->instance('request', $request);

        $middleware = new ForcePasswordChange;
        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        // It should NOT redirect, it should pass through to the next closure
        $this->assertEquals('OK', $response->getContent());
    }
}

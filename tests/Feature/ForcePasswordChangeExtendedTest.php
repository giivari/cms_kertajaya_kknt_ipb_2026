<?php

namespace Tests\Feature;

use App\Http\Middleware\ForcePasswordChange;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForcePasswordChangeExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function runMiddleware(Request $request)
    {
        $middleware = new ForcePasswordChange();
        return $middleware->handle($request, function ($req) {
            return response('Next called', 200);
        });
    }

    public function test_normal_admin_can_access_protected_functionality()
    {
        $admin = Admin::factory()->create(['force_password_change' => false]);
        $this->actingAs($admin, 'web');

        $request = Request::create(route('filament.admin.pages.dashboard'), 'GET');
        
        $response = $this->runMiddleware($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Next called', $response->getContent());
    }

    public function test_forced_reset_admin_is_redirected()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin, 'web');

        $request = Request::create(route('filament.admin.pages.dashboard'), 'GET');
        
        $response = $this->runMiddleware($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_forced_reset_admin_can_access_password_change_screen()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin, 'web');

        $request = Request::create(route('filament.admin.auth.profile'), 'GET');
        // Route parameter matching is needed for routeIs inside middleware
        $request->setRouteResolver(function () {
            return app('router')->getRoutes()->match(request()->create(route('filament.admin.auth.profile')));
        });
        
        $response = $this->runMiddleware($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_forced_reset_admin_cannot_invoke_unrelated_livewire_action()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin, 'web');

        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('X-Livewire', 'true');
        
        $payload = [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => [
                            'name' => 'app.filament.resources.pages.page-resource.pages.list-pages',
                        ],
                    ]),
                ]
            ]
        ];
        $request->merge($payload);

        $response = $this->runMiddleware($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_forced_reset_admin_cannot_update_website_settings()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin, 'web');

        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('X-Livewire', 'true');
        
        $payload = [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => [
                            'name' => 'app.filament.pages.website-settings',
                        ],
                    ]),
                ]
            ]
        ];
        $request->merge($payload);

        $response = $this->runMiddleware($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }

    public function test_forced_reset_admin_cannot_trigger_media_action()
    {
        $admin = Admin::factory()->create(['force_password_change' => true]);
        $this->actingAs($admin, 'web');

        $request = Request::create('/livewire/update', 'POST');
        $request->headers->set('X-Livewire', 'true');
        
        $payload = [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => [
                            'name' => 'app.filament.resources.media.media-resource.pages.list-media',
                        ],
                    ]),
                ]
            ]
        ];
        $request->merge($payload);

        $response = $this->runMiddleware($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(filament()->getProfileUrl(), $response->getTargetUrl());
    }
}

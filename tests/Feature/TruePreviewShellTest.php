<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\Location;
use App\Models\Media;
use App\Models\Menu;
use App\Models\News;
use App\Models\Page;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = new PreviewTokenStore();
    $this->admin = Admin::factory()->create();

    $this->previewType = 'page';
    $this->payload = ['version' => 1, 'type' => 'page', 'mode' => 'create', 'record_id' => null, 'state' => ['title' => 'SUPER_SECRET_PAYLOAD_TITLE_123', 'content' => 'Some content'], 'snapshot' => []];

    // Official Laravel test helper to start session so it persists into the request
    $this->startSession();

    $session = app('session.store');
    $this->sessionId = $session->getId();

    // Karena SESSION_DRIVER=database, simpan session awal agar ID tersebut memiliki
    // state yang konsisten sebelum request pertama.
    $session->save();

    // Gunakan withCookie agar Laravel testing helper mengenkripsi cookie sebelum request melewati EncryptCookies
    $this->withCookie($session->getName(), $this->sessionId);

    $this->rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, $this->payload);

    $this->routeUrl = route('admin.preview.show', ['token' => $this->rawToken]);
});

test('guest cannot access preview route', function () {
    $this->get($this->routeUrl)->assertRedirect();
});

test('authenticated admin with owner session can access valid token', function () {
    $this->actingAs($this->admin)
         ->get($this->routeUrl)
         ->assertOk();
});

test('invalid token returns 404', function () {
    $this->actingAs($this->admin)
         ->get(route('admin.preview.show', ['token' => 'invalid_token']))
         ->assertNotFound();
});

test('expired token returns 404', function () {
    // Make the token expired without changing the global fail-closed configuration
    PreviewToken::where('token_hash', hash('sha256', $this->rawToken))
        ->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($this->admin)
         ->get($this->routeUrl)
         ->assertNotFound();
});

test('token from another session returns 404', function () {
    $otherToken = $this->store->create($this->admin->id, 'different_session_id_456', $this->previewType, $this->payload);

    $this->actingAs($this->admin)
         ->get(route('admin.preview.show', ['token' => $otherToken]))
         ->assertNotFound();

    // Ensure owner token is still accessible
    $this->actingAs($this->admin)->get($this->routeUrl)->assertOk();
});

test('token from another admin ID returns 404 without creating second admin', function () {
    // Authenticate as a non-persistent in-memory admin to simulate a different user
    $otherAdmin = Admin::factory()->make(['id' => (string) Str::uuid()]);

    $this->actingAs($otherAdmin)
         ->get($this->routeUrl)
         ->assertNotFound();

    // Ensure owner token is still accessible by the real owner
    $this->actingAs($this->admin)->get($this->routeUrl)->assertOk();
});

test('valid response uses public frontend layout and stable landmarks', function () {
    $response = $this->actingAs($this->admin)->get($this->routeUrl);

    $response->assertOk()
             ->assertViewIs('public.preview.placeholder')
             ->assertSee('True Frontend Preview Aktif')
             ->assertSee('<html', false)
             ->assertSee('<body', false);
});

test('valid response does not expose sensitive internals', function () {
    $response = $this->actingAs($this->admin)->get($this->routeUrl);

    $response->assertOk()
             ->assertDontSee($this->payload['state']['title'])
             ->assertDontSee($this->rawToken)
             ->assertDontSee($this->sessionId);

    $tokenRecord = PreviewToken::where('token_hash', hash('sha256', $this->rawToken))->first();
    $response->assertDontSee($tokenRecord->encrypted_payload);
});

test('valid response has required security headers', function () {
    $response = $this->actingAs($this->admin)->get($this->routeUrl);

    $response->assertOk()
             ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
             ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");

    $cacheControlHeader = $response->headers->get('Cache-Control');
    expect($cacheControlHeader)->not->toBeNull();

    $cacheControlDirectives = collect(explode(',', $cacheControlHeader))
        ->map(static fn (string $directive): string => strtolower(trim($directive)))
        ->filter()
        ->values()
        ->all();

    expect($cacheControlDirectives)->toContain('no-store')
                                   ->toContain('private')
                                   ->toContain('no-cache')
                                   ->toContain('must-revalidate')
                                   ->not->toContain('public');
});

test('route retrieval does not delete token allowing refresh', function () {
    $this->actingAs($this->admin)->get($this->routeUrl)->assertOk();
    $this->actingAs($this->admin)->get($this->routeUrl)->assertOk();

    expect(PreviewToken::where('token_hash', hash('sha256', $this->rawToken))->exists())->toBeTrue();
});

test('no business mutations or audit logs occur during retrieval', function () {
    $beforeCounts = [
        'audit' => AuditLog::count(),
        'news' => News::count(),
        'page' => Page::count(),
        'location' => Location::count(),
        'gallery' => GalleryAlbum::count(),
        'document' => Document::count(),
        'media' => Media::count(),
        'menu' => Menu::count(),
    ];

    $this->actingAs($this->admin)->get($this->routeUrl)->assertOk();

    expect(AuditLog::count())->toBe($beforeCounts['audit'])
        ->and(News::count())->toBe($beforeCounts['news'])
        ->and(Page::count())->toBe($beforeCounts['page'])
        ->and(Location::count())->toBe($beforeCounts['location'])
        ->and(GalleryAlbum::count())->toBe($beforeCounts['gallery'])
        ->and(Document::count())->toBe($beforeCounts['document'])
        ->and(Media::count())->toBe($beforeCounts['media'])
        ->and(Menu::count())->toBe($beforeCounts['menu']);
});

test('iframe shell view contains correct controls and sandbox attributes', function () {
    $view = View::make('filament.preview.iframe-shell', [
        'previewUrl' => $this->routeUrl,
        'title' => 'Test Shell',
    ])->render();

    expect($view)->toContain("mode = 'desktop'")
                 ->toContain("mode = 'tablet'")
                 ->toContain("mode = 'mobile'")
                 ->toContain('Buka di Tab Baru')
                 ->toContain('target="_blank"')
                 ->toContain('rel="noopener noreferrer"');

    expect($view)->toContain('<iframe')
                 ->toContain('src="'.$this->routeUrl.'"')
                 ->toContain('sandbox="allow-scripts allow-same-origin"')
                 ->not->toContain('allow-top-navigation')
                 ->not->toContain('allow-forms')
                 ->not->toContain('allow-downloads');

    // Token is safely part of the URL (and technical mounted-action state), so we don't assert its global absence
    expect($view)->not->toContain($this->sessionId)
                 ->not->toContain('C:\\')
                 ->not->toContain('/var/www');
});


test('close button structural check', function () {
    $view = View::make('filament.preview.iframe-shell', [
        'previewUrl' => $this->routeUrl,
        'title' => 'Test Shell',
    ])->render();

    expect($view)->toContain('type="button"')
                 ->toContain('aria-label="Tutup Pratinjau"')
                 ->toContain('x-on:click="close()"');
});




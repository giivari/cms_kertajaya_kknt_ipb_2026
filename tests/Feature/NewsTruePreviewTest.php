<?php

use App\Filament\Support\PreviewAction;
use App\Filament\Support\PreviewStateNormalizer;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Media;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Admin::factory()->create();

    $this->startSession();
    $session = app('session.store');
    $this->sessionId = $session->getId();
    $session->save();
    $this->withCookie($session->getName(), $this->sessionId);
});

test('preview token store payload renderer and authenticated endpoint preserve unsaved state without persistence', function () {
    $category = NewsCategory::create(['name' => 'Tech']);
    $media = Media::factory()->create(['filename' => 'test.jpg', 'original_filename' => 'test.jpg', 'directory' => 'media', 'disk' => 'public', 'size' => 1024, 'mime_type' => 'image/jpeg']);
    $before = [News::count(), AuditLog::count()];

    $createPayload = [
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'record_id' => null,
        'state' => PreviewStateNormalizer::normalize('news', [
            'title' => 'Unsaved Title',
            'excerpt' => 'Unsaved excerpt',
            'content' => '<p>Unsaved rich content</p><script>alert("xss")</script><a href="javascript:alert(1)">click</a><img src="x" onerror="alert(\'xss\')">',
            'status' => 'draft',
            'news_category_id' => $category->id,
            'featured_media_id' => $media->id,
        ]),
        'snapshot' => null,
    ];

    $rawToken = app(PreviewTokenStore::class)->create(
        $this->admin->id,
        $this->sessionId,
        'news',
        $createPayload,
    );

    $this->actingAs($this->admin)->get(route('admin.preview.show', ['token' => $rawToken]))
         ->assertOk()
         ->assertSee('Unsaved Title')
         ->assertSee('Unsaved excerpt')
         ->assertSee('Unsaved rich content')
         ->assertSee('Tech')
         ->assertSee($media->url)
         ->assertSee('Draft')
         ->assertDontSeeHtml('<script>alert')
         ->assertDontSeeHtml('onerror=')
         ->assertDontSeeHtml('href="javascript:')
         ->assertDontSee($rawToken);

    expect([News::count(), AuditLog::count()])->toBe($before);

    $news = News::create(['title' => 'Old Title', 'content' => 'Old content', 'status' => 'published', 'published_at' => now(), 'news_category_id' => null])->refresh();
    $identity = [$news->title, $news->content, $news->slug, $news->published_at?->format('Y-m-d H:i:s.u')];
    $auditCount = AuditLog::count();

    $rawEditToken = app(PreviewTokenStore::class)->create(
        $this->admin->id,
        $this->sessionId,
        'news',
        [
            'version' => 1,
            'type' => 'news',
            'mode' => 'edit',
            'record_id' => $news->id,
            'state' => ['title' => 'New Merged Title'],
            'snapshot' => $news->toArray(),
        ]
    );

    $this->actingAs($this->admin)->get(route('admin.preview.show', ['token' => $rawEditToken]))
         ->assertOk()
         ->assertSee('New Merged Title')
         ->assertSee('Old content');

    $news->refresh();
    expect([$news->title, $news->content, $news->slug, $news->published_at?->format('Y-m-d H:i:s.u')])->toBe($identity)
        ->and(AuditLog::count())->toBe($auditCount);
});

test('guard script rules are applied to preview response', function () {
    $payload = [
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'record_id' => null,
        'state' => ['title' => 'Test Guard'],
        'snapshot' => [],
    ];
    $token = app(\App\Services\Preview\PreviewTokenStore::class)->create($this->admin->id, $this->sessionId, 'news', $payload);

    $response = $this->actingAs($this->admin)->get(route('admin.preview.show', ['token' => $token]))
         ->assertOk()
         ->assertViewIs('public.news.show')
         ->assertSee('Test Guard');

    $html = $response->getContent();

    expect($html)->toContain('Link navigation/execution disabled in preview mode.')
                 ->toContain('Form submission disabled in preview mode.')
                 ->not->toContain($token)
                 ->not->toContain($this->sessionId);

    $tokenRecord = PreviewToken::where('token_hash', hash('sha256', $token))->first();
    expect($html)->not->toContain($tokenRecord->encrypted_payload);
});

test('preview context strictly validates create and edit payloads', function () {
    // Missing version
    expect(fn() => \App\Support\Preview\PreviewContext::fromPayload(['type' => 'news']))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    // Create mode with record_id: harus gagal karena record_id, bukan snapshot.
    expect(fn () => \App\Support\Preview\PreviewContext::fromPayload([
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'state' => [],
        'snapshot' => [],
        'record_id' => 99,
    ]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    // Edit mode without record_id: snapshot tetap valid agar kegagalan benar-benar karena record_id.
    expect(fn () => \App\Support\Preview\PreviewContext::fromPayload([
        'version' => 1,
        'type' => 'news',
        'mode' => 'edit',
        'state' => [],
        'snapshot' => ['id' => 99],
        'record_id' => null,
    ]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    // Edit mode with mismatching snapshot id
    expect(fn() => \App\Support\Preview\PreviewContext::fromPayload([
        'version' => 1, 'type' => 'news', 'mode' => 'edit', 'state' => [], 'snapshot' => ['id' => 5], 'record_id' => 99
    ]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    // Valid create.
    $context = \App\Support\Preview\PreviewContext::fromPayload([
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'state' => [],
        'snapshot' => [],
        'record_id' => null,
    ]);
    expect($context->mode)->toBe('create');
});

test('explicit null removes old category and media', function () {
    $category = NewsCategory::create(['name' => 'Old Cat']);
    $news = News::create(['title' => 'Old Title', 'content' => 'Old content', 'status' => 'published', 'published_at' => now(), 'news_category_id' => $category->id])->refresh();

    // Create PreviewContext directly
    $payload = [
        'version' => 1,
        'type' => 'news',
        'mode' => 'edit',
        'record_id' => $news->id,
        'snapshot' => $news->only(['id', 'title', 'news_category_id']),
        'state' => ['title' => 'New Merged Title', 'news_category_id' => null, 'featured_media_id' => null]
    ];

    $context = \App\Support\Preview\PreviewContext::fromPayload($payload);

    // Call Renderer
    $renderer = new \App\Support\Preview\Renderers\NewsPreviewRenderer();
    $view = $renderer->render($context);

    $newsItem = $view->getData()['newsItem'];

    expect($newsItem->relationLoaded('category'))->toBeTrue()
        ->and($newsItem->category)->toBeNull()
        ->and($newsItem->relationLoaded('featuredMedia'))->toBeTrue()
        ->and($newsItem->featuredMedia)->toBeNull();

    $news->refresh();
    expect($news->news_category_id)->toBe($category->id);
});

test('existing public news behavior is not changed by preview', function () {
    $draft = News::create(['title' => 'Draft News', 'content' => 'draft', 'status' => 'draft']);

    $this->get(route('news.show', $draft->slug))
         ->assertNotFound();

    $published = News::create(['title' => 'Published News', 'content' => 'pub', 'status' => 'published', 'published_at' => now()]);

    $this->get(route('news.show', $published->slug))
         ->assertOk();
});

test('preview token store preserves distinct normalized form-state payloads', function () {
    $beforeTokenCount = PreviewToken::count();
    $tokenStore = app(PreviewTokenStore::class);
    $token1 = $tokenStore->create($this->admin->id, $this->sessionId, 'news', [
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'record_id' => null,
        'state' => PreviewStateNormalizer::normalize('news', [
            'title' => 'Lifecycle Title 1',
            'status' => 'draft',
        ]),
        'snapshot' => null,
    ]);
    $token2 = $tokenStore->create($this->admin->id, $this->sessionId, 'news', [
        'version' => 1,
        'type' => 'news',
        'mode' => 'create',
        'record_id' => null,
        'state' => PreviewStateNormalizer::normalize('news', [
            'title' => 'Lifecycle Title 2',
            'status' => 'draft',
        ]),
        'snapshot' => null,
    ]);

    expect(PreviewToken::count())->toBe($beforeTokenCount + 2)
        ->and($token1)->not->toBe($token2)
        ->and($tokenStore->retrieve($token1, $this->admin->id, $this->sessionId)['state']['title'])->toBe('Lifecycle Title 1')
        ->and($tokenStore->retrieve($token2, $this->admin->id, $this->sessionId)['state']['title'])->toBe('Lifecycle Title 2');
});

test('constructing non-news preview action does not create token', function () {
    $beforeTokenCount = PreviewToken::count();
    $action = PreviewAction::make('page');

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class)
        ->and(PreviewToken::count())->toBe($beforeTokenCount);
});

test('news action uses correct modal width', function () {
    $action = \App\Filament\Support\PreviewAction::make('news');
    expect($action->getModalWidth())->toBe(\Filament\Support\Enums\Width::Screen);

    $nonNewsAction = \App\Filament\Support\PreviewAction::make('page');
    expect($nonNewsAction->getModalWidth())->toBe(\Filament\Support\Enums\Width::SevenExtraLarge);
});




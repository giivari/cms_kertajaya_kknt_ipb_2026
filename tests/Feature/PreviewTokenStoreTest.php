<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\News;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = new PreviewTokenStore();
    $this->admin = Admin::factory()->create();
    $this->sessionId = Str::random(40);
    $this->previewType = 'news';
});

test('can create and retrieve a valid preview token by the correct owner', function () {
    $payload = ['title' => 'Test Preview', 'content' => 'Some content'];

    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, $payload);

    expect($rawToken)->toBeString()->toHaveLength(64);

    $retrieved = $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);

    expect($retrieved)->toBeArray()
        ->and($retrieved)->toEqual($payload);
});

test('raw token is not stored in the database', function () {
    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);

    $exists = PreviewToken::where('token_hash', $rawToken)->exists();
    expect($exists)->toBeFalse();
});

test('database payload is encrypted and does not contain plaintext fixture data', function () {
    $sensitiveString = 'SUPER_SECRET_PLAINTEXT_FIXTURE_123';
    $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['secret' => $sensitiveString]);

    $record = PreviewToken::first();

    expect($record->encrypted_payload)->not->toContain($sensitiveString)
        ->and(str_contains($record->encrypted_payload, $sensitiveString))->toBeFalse();
});

test('invalid token is rejected', function () {
    $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);

    $retrieved = $this->store->retrieve('invalid_raw_token_xyz', $this->admin->id, $this->sessionId);
    expect($retrieved)->toBeNull();
});

test('token from another admin is rejected', function () {
    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);
    $otherAdminId = (string) Str::uuid();

    $retrievedByOther = $this->store->retrieve($rawToken, $otherAdminId, $this->sessionId);
    expect($retrievedByOther)->toBeNull();

    $retrievedByOwner = $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);
    expect($retrievedByOwner)->toBeArray()->toHaveKey('data', 'test');
});

test('token from another session is rejected', function () {
    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);

    $retrieved = $this->store->retrieve($rawToken, $this->admin->id, 'different_session_id');
    expect($retrieved)->toBeNull();
});

test('expired token is rejected', function () {
    Config::set('preview.ttl_minutes', -1); // Expired immediately
    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);

    $retrieved = $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);
    expect($retrieved)->toBeNull();
});

test('revoke only succeeds for the owner', function () {
    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);
    $otherAdminId = (string) Str::uuid();

    $revokedOther = $this->store->revoke($rawToken, $otherAdminId, $this->sessionId);
    expect($revokedOther)->toBeFalse()
        ->and(PreviewToken::count())->toBe(1);

    $retrievedByOwner = $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);
    expect($retrievedByOwner)->not->toBeNull();

    $revokedOwner = $this->store->revoke($rawToken, $this->admin->id, $this->sessionId);
    expect($revokedOwner)->toBeTrue()
        ->and(PreviewToken::count())->toBe(0);

    $retrievedAfterRevoke = $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);
    expect($retrievedAfterRevoke)->toBeNull();
});

test('unsupported preview type is rejected', function () {
    expect(fn () => $this->store->create($this->admin->id, $this->sessionId, 'invalid_type', ['data' => 'test']))
        ->toThrow(\InvalidArgumentException::class, 'Unsupported preview type');
});

test('payload above max limit is rejected', function () {
    Config::set('preview.max_payload_bytes', 10);

    expect(fn () => $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['large' => 'payload data']))
        ->toThrow(\LengthException::class, 'Preview payload size exceeds maximum limit');
});

test('payload containing objects, closures, or resources is rejected', function () {
    $payloadObj = ['nested' => ['obj' => new \stdClass()]];
    $payloadClosure = ['func' => fn() => 'test'];
    $payloadResource = ['file' => fopen('php://memory', 'r')];

    expect(fn () => $this->store->create($this->admin->id, $this->sessionId, $this->previewType, $payloadObj))
        ->toThrow(\InvalidArgumentException::class, 'Objects are not allowed in preview payload');

    expect(fn () => $this->store->create($this->admin->id, $this->sessionId, $this->previewType, $payloadClosure))
        ->toThrow(\InvalidArgumentException::class, 'Objects are not allowed in preview payload');

    expect(fn () => $this->store->create($this->admin->id, $this->sessionId, $this->previewType, $payloadResource))
        ->toThrow(\InvalidArgumentException::class, 'Resources are not allowed in preview payload');
});

test('maximum 5 active tokens are maintained per session and oldest is removed', function () {
    Config::set('preview.max_active', 5);

    for ($i = 1; $i <= 6; $i++) {
        $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['iteration' => $i]);
        // sleep a tiny bit to ensure distinct created_at, or just trust the DB autoincrement ID logic inside the store which orders by created_at.
        // Actually our logic orders by created_at desc.
        \Carbon\Carbon::setTestNow(now()->addSeconds(1));
    }

    expect(PreviewToken::count())->toBe(5);

    // The oldest one (iteration 1) should be gone, meaning we can't retrieve it.
    // We check the remaining payloads.
    $tokens = PreviewToken::orderBy('created_at', 'asc')->get();
    $firstRemaining = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($tokens->first()->encrypted_payload), true);
    expect($firstRemaining['iteration'])->toBe(2);
});

test('expired tokens can be pruned explicitly', function () {
    \Carbon\Carbon::setTestNow(now()->subMinutes(60));
    $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['data' => 'test']);

    \Carbon\Carbon::setTestNow(); // reset to real now

    expect(PreviewToken::count())->toBe(1);

    $pruned = $this->store->pruneExpired();
    expect($pruned)->toBe(1)
        ->and(PreviewToken::count())->toBe(0);
});

test('no audit logs or business mutations occur during preview token lifecycle', function () {
    $beforeCounts = [
        'audit' => AuditLog::count(),
        'news' => News::count(),
    ];

    $rawToken = $this->store->create($this->admin->id, $this->sessionId, $this->previewType, ['title' => 'Test News']);
    $this->store->retrieve($rawToken, $this->admin->id, $this->sessionId);
    $this->store->revoke($rawToken, $this->admin->id, $this->sessionId);

    expect(AuditLog::count())->toBe($beforeCounts['audit'])
        ->and(News::count())->toBe($beforeCounts['news']);
});

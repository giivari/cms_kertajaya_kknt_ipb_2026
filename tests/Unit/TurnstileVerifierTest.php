<?php

namespace Tests\Unit;

use App\Services\TurnstileVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileVerifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.secret' => 'test-only-placeholder']);
        Http::preventStrayRequests();
    }

    public function test_verify_returns_false_if_token_empty()
    {
        $verifier = app(TurnstileVerifier::class);
        $this->assertFalse($verifier->verify(null));
        $this->assertFalse($verifier->verify(''));
    }

    public function test_missing_secret_is_an_explicit_configuration_failure()
    {
        config(['services.turnstile.secret' => null]);
        
        $verifier = app(TurnstileVerifier::class);
        try {
            $verifier->verify('any-token');
            $this->fail('Missing configuration must never accept verification.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        Http::assertNothingSent();
    }

    public function test_malformed_configuration_never_reaches_the_service(): void
    {
        config(['services.turnstile.secret' => ['unexpected' => 'array']]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(TurnstileVerifier::class)->verify('any-token');
    }

    public function test_verify_returns_true_on_success()
    {
        config(['services.turnstile.secret' => 'secret']);
        
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $verifier = app(TurnstileVerifier::class);
        $this->assertTrue($verifier->verify('valid-token', '1.1.1.1'));
    }

    public function test_verify_returns_false_on_invalid_token()
    {
        config(['services.turnstile.secret' => 'secret']);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
        ]);

        $verifier = app(TurnstileVerifier::class);
        $this->assertFalse($verifier->verify('invalid-token', '1.1.1.1'));
    }

    public function test_verify_returns_false_on_http_500()
    {
        config(['services.turnstile.secret' => 'secret']);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response('Internal Server Error', 500),
        ]);

        $verifier = app(TurnstileVerifier::class);
        $this->assertFalse($verifier->verify('token', '1.1.1.1'));
    }

    public function test_verify_returns_false_on_invalid_json()
    {
        config(['services.turnstile.secret' => 'secret']);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response('not-json', 200),
        ]);

        $verifier = app(TurnstileVerifier::class);
        $this->assertFalse($verifier->verify('token', '1.1.1.1'));
    }

    public function test_verify_returns_false_on_timeout()
    {
        config(['services.turnstile.secret' => 'secret']);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => function () {
                throw new ConnectionException('Timeout');
            },
        ]);

        $verifier = app(TurnstileVerifier::class);
        $this->assertFalse($verifier->verify('valid-token', '1.1.1.1'));
    }
}

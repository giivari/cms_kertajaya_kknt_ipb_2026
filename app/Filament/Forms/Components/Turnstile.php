<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;

class Turnstile extends Field
{
    protected string $view = 'filament.forms.components.turnstile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->required();

        $this->rule(function () {
            return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) {
                if (empty($value)) {
                    $fail('The CAPTCHA verification is required.');

                    return;
                }

                $secret = env('TURNSTILE_SECRET_KEY');

                // For testing with official test keys or if not set, we can allow or use real verification
                if (empty($secret)) {
                    return;
                }

                $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

                if (! $response->json('success')) {
                    $fail('The CAPTCHA verification failed.');
                }
            };
        });
    }
}

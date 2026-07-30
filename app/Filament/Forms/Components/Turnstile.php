<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Http\Client\ConnectionException;
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

                $verifier = app(\App\Services\TurnstileVerifier::class);

                try {
                    if (! $verifier->verify($value, request()->ip())) {
                        $fail('The CAPTCHA verification failed.');
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $fail('The CAPTCHA verification failed due to a timeout.');
                }
            };
        });
    }
}

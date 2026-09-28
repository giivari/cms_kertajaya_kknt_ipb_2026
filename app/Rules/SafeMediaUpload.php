<?php

namespace App\Rules;

use App\Services\MediaInputPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SafeMediaUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Berkas unggahan tidak valid.');
            return;
        }

        try {
            app(MediaInputPolicy::class)->inspect($value->getRealPath(), $value->getClientOriginalName());
        } catch (ValidationException $exception) {
            $fail($exception->validator->errors()->first());
        }
    }
}

<?php

namespace App\Rules;

use App\Services\DocumentFilePolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SafeDocumentUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Berkas unggahan tidak valid.');
            return;
        }

        try {
            app(DocumentFilePolicy::class)->inspect($value->getRealPath(), $value->getClientOriginalName());
        } catch (ValidationException $exception) {
            $fail($exception->validator->errors()->first());
        }
    }
}

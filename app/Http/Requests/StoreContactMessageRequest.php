<?php

namespace App\Http\Requests;

use App\Services\TurnstileVerifier;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize CRLF to LF
        if ($this->has('message') && is_string($this->message)) {
            $this->merge([
                'message' => str_replace("\r\n", "\n", $this->message)
            ]);
        }
        
        // Trim
        foreach (['name', 'contact_type', 'contact_value', 'subject'] as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => trim($this->$field)
                ]);
            }
        }

        if ($this->has('contact_type') && $this->contact_type === 'email' && $this->has('contact_value') && is_string($this->contact_value)) {
            $this->merge([
                'contact_value' => strtolower($this->contact_value)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:100',
                'regex:/^[^\n\r\x00-\x1F]+$/'
            ],
            'contact_type' => ['required', 'string', 'in:email,phone'],
            'contact_value' => [
                'required', 'string', 'max:150',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[\n\r\x00-\x1F]/', $value)) {
                        $fail('The '.$attribute.' contains invalid characters.');
                        return;
                    }
                    if ($this->contact_type === 'email') {
                        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail('Format email tidak valid.');
                        }
                    } elseif ($this->contact_type === 'phone') {
                        if (! preg_match('/^\+?[0-9\s\-]+$/', $value)) {
                            $fail('Format nomor telepon tidak valid.');
                        }
                    }
                }
            ],
            'subject' => [
                'required', 'string', 'max:150',
                'regex:/^[^\n\r\x00-\x1F]+$/'
            ],
            'message' => [
                'required', 'string', 'min:10', 'max:2000',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
                        $fail('Pesan mengandung karakter tidak valid.');
                    }
                    if ($value !== strip_tags($value)) {
                        $fail('Pesan tidak boleh mengandung tag HTML.');
                    }
                }
            ],
            'cf-turnstile-response' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                // If base validation failed, don't ping Turnstile
                if ($validator->errors()->isEmpty()) {
                    $verifier = app(TurnstileVerifier::class);
                    $token = $this->input('cf-turnstile-response');
                    
                    if (! $verifier->verify($token, $this->ip())) {
                        $validator->errors()->add('cf-turnstile-response', 'Verifikasi keamanan gagal.');
                    }
                }
            }
        ];
    }
}
<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Use max_upload_size from SettingsService, convert MB to KB
        $maxSizeMB = (int) SettingsService::get('max_upload_size', 10);
        $maxSizeKB = $maxSizeMB * 1024;

        $maxWidth = (int) SettingsService::get('max_image_width', 3840);
        $maxHeight = (int) SettingsService::get('max_image_height', 2160);

        $rules = [
            'file' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                "max:{$maxSizeKB}",
            ],
            'original_filename' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:500',
            'caption' => 'nullable|string|max:1000',
        ];

        if ($this->hasFile('file')) {
            $mimeType = $this->file('file')->getMimeType();
            if (str_starts_with($mimeType ?? '', 'image/')) {
                $rules['file'][] = "dimensions:max_width={$maxWidth},max_height={$maxHeight}";
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file') && $this->file('file')->isValid()) {
                $file = $this->file('file');
                $mimeType = $file->getMimeType();

                if ($mimeType === 'application/pdf') {
                    $content = file_get_contents($file->path());

                    // Conservative checks for risky PDF elements
                    if (stripos($content, '/JavaScript') !== false ||
                        stripos($content, '/JS') !== false ||
                        stripos($content, '/Launch') !== false ||
                        stripos($content, '/EmbeddedFiles') !== false) {

                        $validator->errors()->add('file', 'The PDF contains potentially unsafe elements and was rejected.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.mimetypes' => 'Only JPEG, PNG, WebP images and PDF documents are allowed.',
            'file.max' => 'The file size exceeds the maximum allowed limit.',
        ];
    }
}

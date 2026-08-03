<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public static function get(string $key, $default = null)
    {
        if (app()->has(\App\Support\Preview\PreviewContext::class)) {
            $context = app(\App\Support\Preview\PreviewContext::class);
            if ($context->previewType === 'settings' && isset($context->normalizedState[$key])) {
                return $context->normalizedState[$key];
            }
        }

        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = WebsiteSetting::find($key);

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value)
    {
        WebsiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget("settings.{$key}");
    }
}

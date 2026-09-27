<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public static function get(string $key, $default = null)
    {
        if (app()->has(\App\Support\Preview\PreviewContext::class)) {
            $context = app(\App\Support\Preview\PreviewContext::class);
            if ($context->previewType === 'settings' && array_key_exists($key, $context->normalizedState)) {
                return self::safeDisplayValue($key, $context->normalizedState[$key]);
            }
        }

        $cached = Cache::rememberForever("settings.{$key}", function () use ($key): array {
            $setting = WebsiteSetting::find($key);

            return [
                '_settings_contract' => true,
                'exists' => $setting !== null,
                'value' => $setting?->value,
            ];
        });

        // A cache entry written before the settings contract stored a raw value.
        // Refresh it once rather than allowing the first caller's default to win.
        if (! is_array($cached) || ($cached['_settings_contract'] ?? false) !== true) {
            Cache::forget("settings.{$key}");

            return self::get($key, $default);
        }

        $value = $cached['exists'] ? $cached['value'] : $default;

        return self::safeDisplayValue($key, $value);
    }

    private static function safeDisplayValue(string $key, mixed $value): mixed
    {
        return $value !== null && in_array($key, \App\Support\ContentSecurity::SETTING_URL_KEYS, true)
            ? \App\Support\ContentSecurity::url($value)
            : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::setMany([$key => $value]);
    }

    /** @param array<string, mixed> $settings */
    public static function setMany(array $settings): void
    {
        $settings = collect($settings)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) && $key !== '')
            ->all();

        DB::transaction(function () use ($settings): void {
            $mediaSettings = array_intersect_key($settings, array_flip(MediaReferenceCoordinator::SETTING_KEYS));
            $previousMediaIds = WebsiteSetting::query()
                ->whereIn('key', array_keys($mediaSettings))
                ->get()
                ->pluck('value')
                ->all();
            app(MediaReferenceCoordinator::class)->lockReferences([
                ...array_values($previousMediaIds),
                ...array_values($mediaSettings),
            ]);

            foreach ($settings as $key => $value) {
                WebsiteSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value],
                );
            }

            DB::afterCommit(function () use ($settings): void {
                foreach (array_keys($settings) as $key) {
                    Cache::forget("settings.{$key}");
                }
            });
        });
    }
}

<?php

namespace App\Models\Concerns;

use App\Services\AuditLogService;

trait Auditable
{
    protected static array $redactedKeys = [
        'password',
        'app_authentication_secret',
        'current_password',
        'password_confirmation',
        'totp',
        'captcha',
        'remember_token',
    ];

    protected static function redact(array $data): array
    {
        foreach (self::$redactedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLogService::log('created', $model, null, self::redact($model->getAttributes()));
        });

        static::updated(function ($model) {
            AuditLogService::log('updated', $model, self::redact($model->getOriginal()), self::redact($model->getChanges()));
        });

        static::deleted(function ($model) {
            AuditLogService::log('deleted', $model, self::redact($model->getAttributes()), null);
        });
    }
}

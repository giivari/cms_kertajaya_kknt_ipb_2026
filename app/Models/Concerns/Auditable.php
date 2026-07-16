<?php

namespace App\Models\Concerns;

use App\Models\Admin;
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

    protected static function redact(array $data, string $modelClass): array
    {
        if ($modelClass === Admin::class) {
            $allowlist = [
                'id',
                'username',
                'name',
                'email',
                'force_password_change',
                'password_changed_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            $filtered = [];
            foreach ($allowlist as $key) {
                if (array_key_exists($key, $data)) {
                    $filtered[$key] = $data[$key];
                }
            }

            if (array_key_exists('app_authentication_secret', $data)) {
                $filtered['mfa_enabled'] = ! empty($data['app_authentication_secret']);
            }

            return $filtered;
        }

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
            AuditLogService::log('created', $model, null, self::redact($model->getAttributes(), get_class($model)));
        });

        static::updated(function ($model) {
            AuditLogService::log('updated', $model, self::redact($model->getOriginal(), get_class($model)), self::redact($model->getChanges(), get_class($model)));
        });

        static::deleted(function ($model) {
            AuditLogService::log('deleted', $model, self::redact($model->getAttributes(), get_class($model)), null);
        });
    }
}

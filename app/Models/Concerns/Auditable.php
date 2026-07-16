<?php

namespace App\Models\Concerns;

use App\Services\AuditLogService;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLogService::log('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            AuditLogService::log('updated', $model, $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            AuditLogService::log('deleted', $model, $model->getAttributes(), null);
        });
    }
}

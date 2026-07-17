<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Admin;

class AuditLogPolicy
{
    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function view(Admin $user, AuditLog $auditLog): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return false;
    }

    public function update(Admin $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(Admin $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function restore(Admin $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function forceDelete(Admin $user, AuditLog $auditLog): bool
    {
        return false;
    }
}

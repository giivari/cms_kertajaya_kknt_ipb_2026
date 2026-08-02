<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\LocationCategory;

class LocationCategoryPolicy
{
    public function viewAny(Admin $admin): bool { return true; }
    public function view(Admin $admin, LocationCategory $locationCategory): bool { return true; }
    public function create(Admin $admin): bool { return true; }
    public function update(Admin $admin, LocationCategory $locationCategory): bool { return true; }
    public function delete(Admin $admin, LocationCategory $locationCategory): bool
    {
        return $locationCategory->locations()->doesntExist();
    }
    public function restore(Admin $admin, LocationCategory $locationCategory): bool { return true; }
    public function forceDelete(Admin $admin, LocationCategory $locationCategory): bool
    {
        return $locationCategory->locations()->doesntExist();
    }
}

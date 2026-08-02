<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Location;

class LocationPolicy
{
    public function viewAny(Admin $admin): bool { return true; }
    public function view(Admin $admin, Location $location): bool { return true; }
    public function create(Admin $admin): bool { return true; }
    public function update(Admin $admin, Location $location): bool { return true; }
    public function delete(Admin $admin, Location $location): bool { return true; }
    public function restore(Admin $admin, Location $location): bool { return true; }
    public function forceDelete(Admin $admin, Location $location): bool { return true; }
}

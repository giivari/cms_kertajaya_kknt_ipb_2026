<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Menu;

class MenuPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    public function view(Admin $admin, Menu $menu): bool
    {
        return true;
    }

    public function create(Admin $admin): bool
    {
        return true;
    }

    public function update(Admin $admin, Menu $menu): bool
    {
        return true;
    }

    public function delete(Admin $admin, Menu $menu): bool
    {
        return true;
    }

    public function restore(Admin $admin, Menu $menu): bool
    {
        return true;
    }

    public function forceDelete(Admin $admin, Menu $menu): bool
    {
        return true;
    }
}

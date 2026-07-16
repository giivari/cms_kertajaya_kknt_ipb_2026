<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\NewsCategory;
use Illuminate\Auth\Access\Response;

class NewsCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin $admin, NewsCategory $newsCategory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin $admin, NewsCategory $newsCategory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin $admin, NewsCategory $newsCategory): bool
    {
        return $newsCategory->news()->doesntExist();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Admin $admin, NewsCategory $newsCategory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Admin $admin, NewsCategory $newsCategory): bool
    {
        return $newsCategory->news()->doesntExist();
    }
}

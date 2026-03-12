<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class SettingsPolicy
{
    /**
     * Determine whether the user can view settings.
     */
    public function view_settings(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can update settings.
     */
    public function update_settings(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->hasRole(['admin']);
    }
}

<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Admin → System settings (auto-assign, who is on leave; later the staff import).
 * Registered by Laravel's App\Policies\{Model}Policy discovery for App\Models\Setting.
 */
class SettingPolicy
{
    public function manage(User $user): Response
    {
        return $user->checkPermissionTo(PermissionName::ManageSettings)
            ? Response::allow()
            : Response::deny('Only an Admin can change system settings.');
    }
}

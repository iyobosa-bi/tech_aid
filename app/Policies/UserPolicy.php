<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Admin → Users: who may see the user list and switch accounts off, back on, or delete them.
 * Only "manage users" (Admin). Nobody can deactivate or delete their own account — that also
 * means there's always at least one active Admin left.
 */
class UserPolicy
{
    private const ADMIN_ONLY = 'Only an Admin can manage user accounts.';

    public function viewAny(User $user): Response
    {
        return $this->isAdmin($user) ? Response::allow() : Response::deny(self::ADMIN_ONLY);
    }

    public function deactivate(User $user, User $target): Response
    {
        if (! $this->isAdmin($user)) {
            return Response::deny(self::ADMIN_ONLY);
        }

        if ($user->is($target)) {
            return Response::deny('You can\'t deactivate your own account.');
        }

        return $target->isActive() ? Response::allow() : Response::deny("{$target->name}'s account is already deactivated.");
    }

    public function activate(User $user, User $target): Response
    {
        if (! $this->isAdmin($user)) {
            return Response::deny(self::ADMIN_ONLY);
        }

        return $target->isActive() ? Response::deny("{$target->name}'s account is already active.") : Response::allow();
    }

    public function delete(User $user, User $target): Response
    {
        if (! $this->isAdmin($user)) {
            return Response::deny(self::ADMIN_ONLY);
        }

        return $user->is($target) ? Response::deny('You can\'t delete your own account.') : Response::allow();
    }

    private function isAdmin(User $user): bool
    {
        return $user->checkPermissionTo(PermissionName::ManageUsers);
    }
}

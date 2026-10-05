<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Active (not deactivated) users holding the role.
     *
     * @return Collection<int, User>
     */
    public function withRole(RoleName $role): Collection
    {
        return User::role($role->value)->get();
    }
}

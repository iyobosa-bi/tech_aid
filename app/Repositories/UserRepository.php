<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Enums\TicketStatus;
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

    /**
     * Active Application Support staff, each with `open_tickets_count` (Assigned, In Progress,
     * Reopened) and `last_assigned_at` (newest ticket they hold). Deactivated users are left out
     * by the soft-delete scope; on-leave staff are included — callers decide what to do with them.
     *
     * @return Collection<int, User>
     */
    public function supportStaffWithWorkload(): Collection
    {
        $workload = array_map(fn (TicketStatus $status) => $status->value, TicketStatus::supportWorkload());

        return User::role(RoleName::ApplicationSupport->value)
            ->withCount(['ticketsAssignedToMe as open_tickets_count' => fn ($query) => $query->whereIn('status', $workload)])
            ->withMax('ticketsAssignedToMe as last_assigned_at', 'assigned_at')
            ->orderBy('name')
            ->get();
    }

    public function findSupportStaff(int $id): User
    {
        return User::role(RoleName::ApplicationSupport->value)->findOrFail($id);
    }

    public function setOnLeave(User $user, bool $onLeave): void
    {
        $user->update(['on_leave' => $onLeave]);
    }
}

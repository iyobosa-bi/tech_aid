<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public const PER_PAGE = 15;

    /**
     * Active users holding the role: not deactivated, and not deleted (the soft-delete scope).
     *
     * @return Collection<int, User>
     */
    public function withRole(RoleName $role): Collection
    {
        return User::role($role->value)->active()->get();
    }

    /**
     * Active Application Support staff, each with `open_tickets_count` (Assigned, In Progress,
     * Reopened) and `last_assigned_at` (newest ticket they hold). Deactivated and deleted users are
     * left out; on-leave staff are included — callers decide what to do with them.
     *
     * @return Collection<int, User>
     */
    public function supportStaffWithWorkload(): Collection
    {
        return User::role(RoleName::ApplicationSupport->value)
            ->active()
            ->withCount(['ticketsAssignedToMe as open_tickets_count' => fn ($query) => $query->whereIn('status', $this->workload())])
            ->withMax('ticketsAssignedToMe as last_assigned_at', 'assigned_at')
            ->orderBy('name')
            ->get();
    }

    public function findSupportStaff(int $id): User
    {
        return User::role(RoleName::ApplicationSupport->value)->active()->findOrFail($id);
    }

    public function setOnLeave(User $user, bool $onLeave): void
    {
        $user->update(['on_leave' => $onLeave]);
    }

    /**
     * Admin → Users. Each user comes with their role, line manager, `direct_reports_count`
     * (active staff who report to them) and `open_tickets_count` (tickets assigned to them still
     * being worked on) — the knock-on effects shown before deactivating or deleting someone.
     *
     * @param  array{search: string, role: ?string, status: ?string}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles:id,name', 'lineManager:id,name'])
            ->withCount([
                'directReports as direct_reports_count' => fn ($query) => $query->whereNull('deactivated_at'),
                'ticketsAssignedToMe as open_tickets_count' => fn ($query) => $query->whereIn('status', $this->workload()),
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $query->where(fn (Builder $q) => $q->whereLike('name', $term)->orWhereLike('email', $term));
            })
            ->when($filters['role'], fn (Builder $query, string $role) => $query->role($role))
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->whereNull('deactivated_at'))
            ->when($filters['status'] === 'deactivated', fn (Builder $query) => $query->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array{total: int, deactivated: int}
     */
    public function accountCounts(): array
    {
        return [
            'total' => User::count(),
            'deactivated' => User::whereNotNull('deactivated_at')->count(),
        ];
    }

    // deactivated_at isn't mass-assignable, so it's only ever set here.
    public function setActive(User $user, bool $active): void
    {
        $user->forceFill(['deactivated_at' => $active ? null : now()])->save();
    }

    // A soft delete: the row stays for the audit trail, and the email can be used for a new account.
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * @return list<string>
     */
    private function workload(): array
    {
        return array_map(fn (TicketStatus $status) => $status->value, TicketStatus::supportWorkload());
    }
}

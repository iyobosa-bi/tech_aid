<?php

namespace App\Repositories;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Dashboard stat queries. Per-role methods take the signed-in user and filter on that
 * user's own column (requester_id / line_manager_id / assigned_to_id); only the org*()
 * methods and activeUserCount() read across the whole organisation, and DashboardService
 * calls those for Head of Service Management and Admin alone. Soft-deleted tickets and
 * users are excluded automatically by their SoftDeletes scopes.
 */
class DashboardRepository
{
    // ── Requester: scoped to requester_id ────────────────────────────────────

    public function requesterOpenCount(User $user): int
    {
        return $this->raisedBy($user)->whereIn('status', $this->values(TicketStatus::open()))->count();
    }

    public function requesterReturnedCount(User $user): int
    {
        return $this->raisedBy($user)->where('status', TicketStatus::Returned->value)->count();
    }

    public function requesterAwaitingRatingCount(User $user): int
    {
        return $this->raisedBy($user)->where('status', TicketStatus::Resolved->value)->doesntHave('rating')->count();
    }

    public function requesterRaisedSinceCount(User $user, Carbon $since): int
    {
        return $this->raisedBy($user)->where('created_at', '>=', $since)->count();
    }

    // ── Line Manager: scoped to line_manager_id ──────────────────────────────

    public function lineManagerPendingApprovalCount(User $user): int
    {
        return $this->managedBy($user)->where('status', TicketStatus::PendingLineManagerApproval->value)->count();
    }

    /**
     * Distinct team tickets with an audit entry of $action since $since — a ticket returned
     * twice in a month counts once. There's no approved_at column, so the history is the record.
     */
    public function lineManagerActionedSinceCount(User $user, TicketAction $action, Carbon $since): int
    {
        return $this->managedBy($user)
            ->whereHas('statusHistory', fn (Builder $history) => $history
                ->where('action', $action->value)
                ->where('ticket_status_history.created_at', '>=', $since))
            ->count();
    }

    public function lineManagerTeamCount(User $user): int
    {
        return $this->managedBy($user)->count();
    }

    // ── Application Support: scoped to assigned_to_id ────────────────────────

    public function supportActiveCount(User $user): int
    {
        return $this->assignedTo($user)->whereIn('status', $this->values(TicketStatus::active()))->count();
    }

    public function supportResolvedSinceCount(User $user, Carbon $since): int
    {
        return $this->assignedTo($user)->where('resolved_at', '>=', $since)->count();
    }

    public function supportAvgResolutionSeconds(User $user): ?float
    {
        return $this->avgResolutionSeconds($this->assignedTo($user));
    }

    public function supportPendingFeedbackCount(User $user): int
    {
        return $this->assignedTo($user)->where('status', TicketStatus::Resolved->value)->doesntHave('rating')->count();
    }

    // ── Org-wide: Head of Service Management and Admin only ──────────────────

    public function orgStatusCount(TicketStatus ...$statuses): int
    {
        return Ticket::query()->whereIn('status', $this->values($statuses))->count();
    }

    public function orgResolvedSinceCount(Carbon $since): int
    {
        return Ticket::query()->where('resolved_at', '>=', $since)->count();
    }

    public function orgAvgResolutionSeconds(): ?float
    {
        return $this->avgResolutionSeconds(Ticket::query());
    }

    /**
     * @return array<string, int> status value => ticket count
     */
    public function orgStatusBreakdown(): array
    {
        return Ticket::query()
            ->select('status')->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function orgAvgRating(): ?float
    {
        // whereHas('ticket') drops ratings whose ticket was soft-deleted.
        $avg = TicketRating::query()->whereHas('ticket')->avg('rating');

        return $avg === null ? null : (float) $avg;
    }

    public function activeUserCount(): int
    {
        // Deactivated staff are soft-deleted, so the default scope already excludes them.
        return User::query()->count();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function raisedBy(User $user): Builder
    {
        return Ticket::query()->where('requester_id', $user->id);
    }

    private function managedBy(User $user): Builder
    {
        return Ticket::query()->where('line_manager_id', $user->id);
    }

    private function assignedTo(User $user): Builder
    {
        return Ticket::query()->where('assigned_to_id', $user->id);
    }

    /**
     * Mean of (resolved_at − created_at) over resolved tickets, computed in the database.
     * Date arithmetic differs per engine: Postgres in dev/prod, SQLite in the test suite.
     */
    private function avgResolutionSeconds(Builder $tickets): ?float
    {
        $seconds = match ($tickets->getConnection()->getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM (resolved_at - created_at))',
            'sqlite' => '(julianday(resolved_at) - julianday(created_at)) * 86400',
            default => 'TIMESTAMPDIFF(SECOND, created_at, resolved_at)',
        };

        $avg = $tickets->whereNotNull('resolved_at')->toBase()->selectRaw("AVG({$seconds}) AS avg_seconds")->value('avg_seconds');

        return $avg === null ? null : (float) $avg;
    }

    /**
     * @param  array<TicketStatus>  $statuses
     * @return list<string>
     */
    private function values(array $statuses): array
    {
        return array_map(fn (TicketStatus $status) => $status->value, array_values($statuses));
    }
}

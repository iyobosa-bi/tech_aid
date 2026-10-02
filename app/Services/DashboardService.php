<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Models\User;
use App\Repositories\DashboardRepository;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;

/**
 * Builds the role-specific stat cards shown above the dashboard's ticket list.
 * Each role branch only asks DashboardRepository for its own figures, so a role
 * never runs another role's (possibly org-wide) queries.
 */
class DashboardService
{
    public function __construct(private DashboardRepository $stats) {}

    /**
     * @return list<array{label: string, value: string, hint: string, icon: string, tone: string, breakdown?: list<array{status: string, count: int}>}>
     */
    public function cardsFor(User $user): array
    {
        $monthStart = now()->startOfMonth();

        return match ($this->dashboardRole($user)) {
            RoleName::Admin => $this->adminCards(),
            RoleName::HeadOfServiceManagement => $this->headOfServiceCards($monthStart),
            RoleName::LineManager => $this->lineManagerCards($user, $monthStart),
            RoleName::ApplicationSupport => $this->supportCards($user, $monthStart),
            RoleName::Requester => $this->requesterCards($user, $monthStart),
        };
    }

    /**
     * One dashboard per user: if someone holds several roles, the broadest wins. Users
     * with no role get the Requester view — every figure there is limited to tickets
     * they raised themselves, so a misconfigured account can never see org-wide data.
     */
    public function dashboardRole(User $user): RoleName
    {
        foreach ([RoleName::Admin, RoleName::HeadOfServiceManagement, RoleName::LineManager, RoleName::ApplicationSupport] as $role) {
            if ($user->hasRole($role->value)) {
                return $role;
            }
        }

        return RoleName::Requester;
    }

    private function requesterCards(User $user, Carbon $monthStart): array
    {
        return [
            $this->card('Open Tickets', $this->count($this->stats->requesterOpenCount($user)), 'Raised by you, still open', 'ticket', 'brand'),
            $this->card('Returned to You', $this->count($this->stats->requesterReturnedCount($user)), 'Need your edits', 'undo-2', 'amber'),
            $this->card('Awaiting Your Rating', $this->count($this->stats->requesterAwaitingRatingCount($user)), 'Resolved, not yet rated', 'star', 'amber'),
            $this->card('Total Raised', $this->count($this->stats->requesterRaisedSinceCount($user, $monthStart)), $this->thisMonth($monthStart), 'calendar-plus', 'teal'),
        ];
    }

    private function lineManagerCards(User $user, Carbon $monthStart): array
    {
        return [
            $this->card('Pending My Approval', $this->count($this->stats->lineManagerPendingApprovalCount($user)), 'Waiting on your decision', 'hourglass', 'amber'),
            $this->card('Approved This Month', $this->count($this->stats->lineManagerActionedSinceCount($user, TicketAction::Approved, $monthStart)), $this->thisMonth($monthStart), 'circle-check', 'teal'),
            $this->card('Returned This Month', $this->count($this->stats->lineManagerActionedSinceCount($user, TicketAction::Rejected, $monthStart)), $this->thisMonth($monthStart), 'undo-2', 'amber'),
            $this->card("My Team's Tickets", $this->count($this->stats->lineManagerTeamCount($user)), 'All statuses', 'users', 'brand'),
        ];
    }

    private function headOfServiceCards(Carbon $monthStart): array
    {
        return [
            $this->card('Pending Assignment', $this->count($this->stats->orgStatusCount(TicketStatus::PendingAssignment)), 'Org-wide, ready to assign', 'inbox', 'amber'),
            $this->card('Active Org-Wide', $this->count($this->stats->orgStatusCount(...TicketStatus::active())), 'Assigned or in progress', 'activity', 'brand'),
            $this->card('Avg Resolution Time', $this->duration($this->stats->orgAvgResolutionSeconds()), 'Org-wide, raised to resolved', 'clock', 'teal'),
            $this->card('Resolved This Month', $this->count($this->stats->orgResolvedSinceCount($monthStart)), 'Org-wide · '.$monthStart->format('F'), 'circle-check', 'teal'),
        ];
    }

    private function supportCards(User $user, Carbon $monthStart): array
    {
        return [
            $this->card('My Assigned', $this->count($this->stats->supportActiveCount($user)), 'Assigned or in progress', 'wrench', 'brand'),
            $this->card('Resolved By Me', $this->count($this->stats->supportResolvedSinceCount($user, $monthStart)), $this->thisMonth($monthStart), 'circle-check', 'teal'),
            $this->card('My Avg Resolution Time', $this->duration($this->stats->supportAvgResolutionSeconds($user)), 'Yours, raised to resolved', 'clock', 'teal'),
            $this->card('Pending Feedback', $this->count($this->stats->supportPendingFeedbackCount($user)), 'Resolved by you, unrated', 'message-square', 'amber'),
        ];
    }

    private function adminCards(): array
    {
        $breakdown = $this->stats->orgStatusBreakdown();

        // Listed in workflow order, skipping statuses with no tickets.
        $rows = collect(TicketStatus::cases())
            ->filter(fn (TicketStatus $status) => ($breakdown[$status->value] ?? 0) > 0)
            ->map(fn (TicketStatus $status) => ['status' => $status->value, 'count' => $breakdown[$status->value]])
            ->values()
            ->all();

        $avgRating = $this->stats->orgAvgRating();

        return [
            $this->card('Total Active Users', $this->count($this->stats->activeUserCount()), 'Excludes deactivated staff', 'users', 'brand'),
            [...$this->card('Org-Wide Ticket Volume', $this->count(array_sum($breakdown)), 'All tickets by status', 'layers', 'brand'), 'breakdown' => $rows],
            $this->card('Org-Wide Avg Resolution', $this->duration($this->stats->orgAvgResolutionSeconds()), 'Raised to resolved', 'clock', 'teal'),
            $this->card('Avg Support Rating', $avgRating === null ? '—' : number_format($avgRating, 1).' / 5', 'Across all rated tickets', 'star', 'amber'),
        ];
    }

    private function card(string $label, string $value, string $hint, string $icon, string $tone): array
    {
        return compact('label', 'value', 'hint', 'icon', 'tone');
    }

    private function count(int $count): string
    {
        return number_format($count);
    }

    // e.g. "2d 4h", "3h 20m"; "—" until at least one ticket has been resolved.
    private function duration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return $seconds < 60
            ? '< 1m'
            : CarbonInterval::seconds((int) round($seconds))->cascade()->forHumans(['short' => true, 'parts' => 2]);
    }

    private function thisMonth(Carbon $monthStart): string
    {
        return 'This month · '.$monthStart->format('F');
    }
}

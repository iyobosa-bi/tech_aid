<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketReassigned;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedToYou;
use App\Notifications\TicketAutoAssigned;
use App\Notifications\TicketReassignedAway;
use App\Notifications\TicketStatusUpdated;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Flows 5–6: who works on a ticket. Head of Service Management assigns and reassigns by hand;
 * with auto-assign ON (SettingService), an approved ticket goes straight to the least busy
 * person in the "bucket" — active Application Support staff who aren't on leave.
 */
class SupportAssignmentService
{
    public function __construct(
        private readonly TicketTransitionService $transitions,
        private readonly UserRepository $users,
    ) {}

    /**
     * Every active Application Support person with their workload, on-leave staff included
     * (the assign picker shows them greyed out; Admin's settings page lists them all).
     *
     * @return Collection<int, User>
     */
    public function staff(): Collection
    {
        return $this->users->supportStaffWithWorkload();
    }

    /**
     * Admin takes someone out of (or back into) the bucket, e.g. while they're on leave.
     * Tickets they already hold stay with them; Head of Service Management can reassign.
     */
    public function setOnLeave(int $supportUserId, bool $onLeave, User $admin): User
    {
        $person = $this->users->findSupportStaff($supportUserId);
        $this->users->setOnLeave($person, $onLeave);

        Log::channel('activity')->info($onLeave ? 'Support staff marked on leave' : 'Support staff back from leave', [
            'actor_id' => $admin->id,
            'actor_name' => $admin->username(),
            'user_id' => $person->id,
            'username' => $person->username(),
        ]);

        return $person;
    }

    /**
     * Who can take a ticket right now.
     *
     * @param  Collection<int, User>|null  $staff  an already-loaded staff() list, to save a query
     * @return Collection<int, User>
     */
    public function bucket(?Collection $staff = null): Collection
    {
        return ($staff ?? $this->staff())->reject(fn (User $user) => $user->on_leave)->values();
    }

    /**
     * Fewest open tickets; on a tie, whoever was given a ticket longest ago (never comes
     * first), then the lowest id so the choice is always the same for the same data.
     *
     * @param  Collection<int, User>|null  $bucket
     */
    public function leastBusy(?Collection $bucket = null): ?User
    {
        return ($bucket ?? $this->bucket())->sort(fn (User $a, User $b) => [$a->open_tickets_count, (string) $a->last_assigned_at, $a->id]
            <=> [$b->open_tickets_count, (string) $b->last_assigned_at, $b->id])->first();
    }

    /**
     * Gives a waiting ticket (Pending Assignment, or Reopened) to $assignee. $by is null when the
     * system does it (auto-assign); $approvedNow folds the approval into the requester's update.
     */
    public function assign(Ticket $ticket, ?User $by, User $assignee, ?string $note = null, bool $approvedNow = false): Ticket
    {
        $ticket = $this->transitions->move(
            $ticket, $by, TicketAction::Assigned,
            from: TicketStatus::from($ticket->status),
            to: TicketStatus::Assigned,
            comment: filled($note) ? $note : null,
            changes: ['assigned_to_id' => $assignee->id, 'assigned_by_id' => $by?->id, 'assigned_at' => now()],
            meta: ['to_assignee_id' => $assignee->id, 'auto' => $by === null],
        );

        TicketAssigned::dispatch($ticket, $by, $assignee);
        $assignee->notify(new TicketAssignedToYou($ticket, $by));
        $ticket->requester->notify(new TicketStatusUpdated($ticket, TicketStatus::Assigned, $assignee, $approvedNow));

        return $ticket;
    }

    /**
     * Auto-assign, straight after the line manager's approval. Returns null (and changes
     * nothing) when everyone in Application Support is on leave or deactivated.
     */
    public function autoAssign(Ticket $ticket): ?Ticket
    {
        $assignee = $this->leastBusy();

        if (! $assignee) {
            return null;
        }

        $ticket = $this->assign($ticket, null, $assignee, approvedNow: true);

        Notification::send($this->users->withRole(RoleName::HeadOfServiceManagement), new TicketAutoAssigned($ticket, $assignee));

        return $ticket;
    }

    /**
     * Flow 6: moves an Assigned / In Progress ticket to a different person. The status stays the
     * same (from = to, docs/06-data-model.md); meta records who it moved from and to.
     */
    public function reassign(Ticket $ticket, User $by, User $assignee, ?string $note = null): Ticket
    {
        $previous = $ticket->assignedTo;
        $status = TicketStatus::from($ticket->status);

        $ticket = $this->transitions->move(
            $ticket, $by, TicketAction::Reassigned,
            from: $status,
            to: $status,
            comment: filled($note) ? $note : null,
            changes: ['assigned_to_id' => $assignee->id, 'assigned_by_id' => $by->id, 'assigned_at' => now()],
            meta: ['from_assignee_id' => $previous?->id, 'to_assignee_id' => $assignee->id],
        );

        TicketReassigned::dispatch($ticket, $by, $previous, $assignee);
        $assignee->notify(new TicketAssignedToYou($ticket, $by, reassigned: true));

        if ($previous && ! $previous->trashed()) {
            $previous->notify(new TicketReassignedAway($ticket, $assignee));
        }

        return $ticket;
    }
}

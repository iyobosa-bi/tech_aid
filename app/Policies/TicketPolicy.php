<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Spatie permissions answer "what kind of user is this"; each method here adds
 * the record-level check — e.g. only the ticket's own line manager may approve.
 */
class TicketPolicy
{
    public function create(User $user): Response
    {
        return $user->checkPermissionTo(PermissionName::CreateTickets)? Response::allow():Response::deny('You do not have permission to create a Ticket');
    }

    // Anyone signed in may open the list; Ticket::scopeVisibleTo() limits which rows they get.
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return in_array($user->id, [$ticket->requester_id, $ticket->line_manager_id, $ticket->assigned_to_id], true)
            || $user->checkPermissionTo(PermissionName::AssignTickets);
    }

    public function approve(User $user, Ticket $ticket): Response
    {
        if (! $user->checkPermissionTo(PermissionName::ApproveTickets) || $ticket->line_manager_id !== $user->id) {
            return Response::deny("Only this ticket's line manager can approve or decline it.");
        }

        // Also reached when the decision was already made in another tab.
        return $this->statusIs($ticket, TicketStatus::PendingLineManagerApproval)
            ? Response::allow()
            : Response::deny('This ticket is no longer awaiting approval.');
    }

    // Declining is the other half of the same decision (Flow 3), so the same rule applies.
    public function decline(User $user, Ticket $ticket): Response
    {
        return $this->approve($user, $ticket);
    }

    // Everyone who can see the ticket can join its conversation until it's closed.
    public function comment(User $user, Ticket $ticket): Response
    {
        if (! $this->view($user, $ticket)) {
            return Response::deny('You do not have access to this ticket.');
        }

        return $this->statusIs($ticket, TicketStatus::Closed)
            ? Response::deny('This ticket is closed, so no new messages can be added.')
            : Response::allow();
    }

    // Flow 4: the requester fixes a returned ticket and sends it back for approval.
    public function resubmit(User $user, Ticket $ticket): Response
    {
        if ($ticket->requester_id !== $user->id || ! $user->checkPermissionTo(PermissionName::CreateTickets)) {
            return Response::deny('Only the person who raised this ticket can edit and resubmit it.');
        }

        return $this->statusIs($ticket, TicketStatus::Returned)
            ? Response::allow()
            : Response::deny('Only returned tickets can be edited and resubmitted.');
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->checkPermissionTo(PermissionName::AssignTickets)
            && $this->statusIs($ticket, TicketStatus::PendingAssignment, TicketStatus::Reopened);
    }
    
    public function reassign(User $user, Ticket $ticket): bool
    {
        return $user->checkPermissionTo(PermissionName::ReassignTickets)
            && $this->statusIs($ticket, TicketStatus::Assigned, TicketStatus::InProgress);
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        $assignedSupport = $user->checkPermissionTo(PermissionName::ResolveTickets)
            && $ticket->assigned_to_id === $user->id
            && $this->statusIs($ticket, TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Reopened);

        // Head of Service Management may resolve directly instead of assigning (Flow 5).
        $directResolve = $user->checkPermissionTo(PermissionName::AssignTickets)
            && $this->statusIs($ticket, TicketStatus::PendingAssignment);

        return $assignedSupport || $directResolve;
    }

    public function rate(User $user, Ticket $ticket): bool
    {
        return $this->isRequesterRespondingToResolution($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $this->isRequesterRespondingToResolution($user, $ticket);
    }

    private function isRequesterRespondingToResolution(User $user, Ticket $ticket): bool
    {
        return $user->checkPermissionTo(PermissionName::RespondToResolutions)
            && $ticket->requester_id === $user->id
            && $this->statusIs($ticket, TicketStatus::Resolved);
    }

    private function statusIs(Ticket $ticket, TicketStatus ...$statuses): bool
    {
        return in_array($ticket->status, array_map(fn (TicketStatus $s) => $s->value, $statuses), true);
    }
}

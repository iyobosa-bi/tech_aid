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

    public function view(User $user, Ticket $ticket): bool
    {
        return in_array($user->id, [$ticket->requester_id, $ticket->line_manager_id, $ticket->assigned_to_id], true)
            || $user->checkPermissionTo(PermissionName::AssignTickets);
    }

    public function approve(User $user, Ticket $ticket): bool
    {
        return $user->checkPermissionTo(PermissionName::ApproveTickets)
            && $ticket->line_manager_id === $user->id
            && $this->statusIs($ticket, TicketStatus::PendingLineManagerApproval);
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

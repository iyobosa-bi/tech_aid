<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketResolved;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusUpdated;

/**
 * Marks a ticket resolved with resolution notes. Used by Head of Service Management to resolve
 * directly (Flow 5); Flow 7 (the assigned support person resolves) reuses it unchanged —
 * TicketPolicy::resolve decides who may, and from which status.
 */
class TicketResolutionService
{
    public function __construct(private readonly TicketTransitionService $transitions) {}

    public function resolve(Ticket $ticket, User $by, string $notes): Ticket
    {
        // The notes are also the history comment, so they appear in the ticket's conversation.
        $ticket = $this->transitions->move(
            $ticket, $by, TicketAction::Resolved,
            from: TicketStatus::from($ticket->status),
            to: TicketStatus::Resolved,
            comment: $notes,
            changes: ['resolution_notes' => $notes, 'resolved_at' => now()],
        );

        TicketResolved::dispatch($ticket, $by);
        $ticket->requester->notify(new TicketStatusUpdated($ticket, TicketStatus::Resolved, notes: $notes));

        return $ticket;
    }
}

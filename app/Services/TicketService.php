<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketResolved;
use App\Events\TicketWorkStarted;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketStatusUpdated;

/**
 * Working a ticket (Flow 7): the assigned Application Support person starts work and resolves it.
 * Resolving is shared with Head of Service Management, who may resolve directly instead of
 * assigning (Flow 5). TicketPolicy decides who may act — only the person the ticket is assigned
 * to, or Head of Service Management while it waits for assignment.
 *
 * Each method runs through TicketTransitionService::move(): one DB::transaction that locks the
 * ticket row (lockForUpdate) and re-checks its status, so a double-click or a second tab can't
 * act twice; then it updates the ticket and writes the ticket_status_history row (actor, role,
 * from/to status, comment) via TicketRepository.
 */
class TicketService
{
    public function __construct(
        private readonly TicketTransitionService $transitions,
        private readonly TicketUploadService $uploads,
    ) {}

    // Flow 7's optional first step: assigned → in_progress.
    public function startProgress(Ticket $ticket, User $by): Ticket
    {
        $ticket = $this->transitions->move(
            $ticket, $by, TicketAction::Started,
            from: TicketStatus::Assigned,
            to: TicketStatus::InProgress,
        );

        TicketWorkStarted::dispatch($ticket, $by);
        $ticket->requester->notify(new TicketStatusUpdated($ticket, TicketStatus::InProgress, assignee: $by));

        return $ticket;
    }

    /**
     * @param  list<string>  $uploadIds  files uploaded through the resolve form (TicketUploadService), optional
     */
    public function resolve(Ticket $ticket, User $by, string $notes, array $uploadIds = []): Ticket
    {
        // Every file is checked before anything is saved, so an expired upload fails the whole step.
        $uploads = $this->uploads->resolveAll($uploadIds);

        // The notes are also the history comment, so they appear in the ticket's conversation;
        // the files are linked to that same entry, so they show under it.
        $ticket = $this->transitions->move(
            $ticket, $by, TicketAction::Resolved,
            from: TicketStatus::from($ticket->status),
            to: TicketStatus::Resolved,
            comment: $notes,
            changes: ['resolution_notes' => $notes, 'resolved_at' => now()],
            during: fn (Ticket $locked, TicketStatusHistory $step) => $this->uploads->attachAll($locked, $by, $uploads, $step),
        );

        $this->uploads->forgetAll($uploadIds);

        TicketResolved::dispatch($ticket, $by, count($uploads));
        $ticket->requester->notify(new TicketStatusUpdated(
            $ticket, TicketStatus::Resolved, notes: $notes, attachmentCount: count($uploads),
        ));

        return $ticket;
    }
}

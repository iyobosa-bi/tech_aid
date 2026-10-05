<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Events\TicketCommentPosted;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketMessagePosted;
use App\Repositories\TicketRepository;
use Illuminate\Support\Facades\Notification;

/**
 * The ticket conversation. Messages are `commented` entries in the audit trail
 * (docs/06-data-model.md), so they're part of the ticket's permanent record.
 */
class TicketCommentService
{
    public function __construct(private readonly TicketRepository $tickets) {}

    public function post(Ticket $ticket, User $author, string $body): TicketStatusHistory
    {
        // One row in one table, so no transaction is needed. Status doesn't change.
        $comment = $this->tickets->logHistory($ticket, [
            'actor_id' => $author->id,
            'actor_role' => $author->getRoleNames()->first(),
            'action' => TicketAction::Commented->value,
            'from_status' => $ticket->status,
            'to_status' => $ticket->status,
            'comment' => $body,
        ]);

        TicketCommentPosted::dispatch($ticket, $author, $comment);
        Notification::send($this->tickets->participantsExcept($ticket, $author), new TicketMessagePosted($ticket, $author, $body));

        return $comment;
    }
}

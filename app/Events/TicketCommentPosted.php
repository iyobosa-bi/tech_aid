<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Someone added a message to the ticket's conversation.
 */
class TicketCommentPosted
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly TicketStatusHistory $comment,
    ) {}
}

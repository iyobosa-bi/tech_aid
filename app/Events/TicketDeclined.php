<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The line manager declined the ticket, returning it to the requester with a comment
 * (it is not closed — Flow 3). Fired after the database transaction commits.
 */
class TicketDeclined
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly string $comment,
    ) {}
}

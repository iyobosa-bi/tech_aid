<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The line manager approved the ticket; it now waits for Head of Service Management.
 * Fired after the database transaction commits.
 */
class TicketApproved
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}

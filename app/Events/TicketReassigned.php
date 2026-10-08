<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Head of Service Management moved the ticket to a different support person (Flow 6).
 * Fired after the database transaction commits.
 */
class TicketReassigned
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly ?User $from,
        public readonly User $to,
    ) {}
}

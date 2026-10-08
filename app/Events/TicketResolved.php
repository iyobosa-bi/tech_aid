<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The ticket was resolved — directly by Head of Service Management (Flow 5), or later by
 * the assigned support person (Flow 7). Fired after the database transaction commits.
 */
class TicketResolved
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}

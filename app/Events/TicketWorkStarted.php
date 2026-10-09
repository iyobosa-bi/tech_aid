<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The assigned Application Support person started work on the ticket (Flow 7: assigned →
 * in_progress). Fired after the database transaction commits.
 */
class TicketWorkStarted
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}

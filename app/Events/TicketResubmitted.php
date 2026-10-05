<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The requester edited a returned ticket and sent it back for approval (Flow 4).
 * Fired after the database transaction commits.
 */
class TicketResubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}

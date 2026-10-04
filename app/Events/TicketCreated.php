<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A ticket was raised and saved. Fired by TicketCreationService after its database
 * transaction commits, so listeners never see a ticket that was rolled back.
 */
class TicketCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}

<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The ticket was given to an Application Support person (Flow 5) — by Head of Service
 * Management, or automatically (least busy) when $actor is null. Fired after the commit.
 */
class TicketAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?User $actor,
        public readonly User $assignee,
    ) {}

    public function automatic(): bool
    {
        return $this->actor === null;
    }
}

<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use Illuminate\Support\Facades\Log;

/**
 * Writes ticket events to the activity log (config/logging.php → 'activity').
 *
 * Registered automatically by Laravel's event discovery: every public method named
 * handle…() listens for the event class it type-hints. Future ticket events (approved,
 * assigned, resolved…) each get a method here with the same shape — past-tense message,
 * ticket_id + ticket_number + actor_id + actor_name, plus any business fields they need.
 */
class LogTicketActivity
{
    public function handleTicketCreated(TicketCreated $event): void
    {
        Log::channel('activity')->info('Ticket created', [
            'ticket_id' => $event->ticket->id,
            'ticket_number' => $event->ticket->ticket_number,
            'actor_id' => $event->actor->id,
            'actor_name' => $event->actor->username(),
        ]);
    }
}

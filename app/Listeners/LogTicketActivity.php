<?php

namespace App\Listeners;

use App\Events\TicketApproved;
use App\Events\TicketCommentPosted;
use App\Events\TicketCreated;
use App\Events\TicketDeclined;
use App\Events\TicketResubmitted;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Writes ticket events to the activity log (config/logging.php → 'activity').
 *
 * Registered automatically by Laravel's event discovery: every public method named
 * handle…() listens for the event class it type-hints. Each new ticket event gets a
 * method here with the same shape — a past-tense message plus context() and any extra
 * business fields. Never message text: comments can contain customer details.
 */
class LogTicketActivity
{
    public function handleTicketCreated(TicketCreated $event): void
    {
        Log::channel('activity')->info('Ticket created', $this->context($event->ticket, $event->actor));
    }

    public function handleTicketApproved(TicketApproved $event): void
    {
        Log::channel('activity')->info('Ticket approved', $this->context($event->ticket, $event->actor));
    }

    public function handleTicketDeclined(TicketDeclined $event): void
    {
        Log::channel('activity')->info('Ticket declined', $this->context($event->ticket, $event->actor));
    }

    public function handleTicketResubmitted(TicketResubmitted $event): void
    {
        Log::channel('activity')->info('Ticket resubmitted', $this->context($event->ticket, $event->actor));
    }

    public function handleTicketCommentPosted(TicketCommentPosted $event): void
    {
        Log::channel('activity')->info('Ticket comment added', [
            ...$this->context($event->ticket, $event->actor),
            'comment_id' => $event->comment->id,
        ]);
    }

    /**
     * @return array{ticket_id: int, ticket_number: string, actor_id: int, actor_name: string}
     */
    private function context(Ticket $ticket, User $actor): array
    {
        return [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'actor_id' => $actor->id,
            'actor_name' => $actor->username(),
        ];
    }
}

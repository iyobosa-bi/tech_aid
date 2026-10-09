<?php

namespace App\Listeners;

use App\Events\TicketApproved;
use App\Events\TicketAssigned;
use App\Events\TicketCommentPosted;
use App\Events\TicketCreated;
use App\Events\TicketDeclined;
use App\Events\TicketReassigned;
use App\Events\TicketResolved;
use App\Events\TicketResubmitted;
use App\Events\TicketWorkStarted;
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

    public function handleTicketAssigned(TicketAssigned $event): void
    {
        Log::channel('activity')->info($event->automatic() ? 'Ticket auto-assigned' : 'Ticket assigned', [
            ...$this->context($event->ticket, $event->actor),
            'assignee_id' => $event->assignee->id,
            'assignee_name' => $event->assignee->username(),
        ]);
    }

    public function handleTicketReassigned(TicketReassigned $event): void
    {
        Log::channel('activity')->info('Ticket reassigned', [
            ...$this->context($event->ticket, $event->actor),
            'from_assignee_id' => $event->from?->id,
            'to_assignee_id' => $event->to->id,
        ]);
    }

    public function handleTicketWorkStarted(TicketWorkStarted $event): void
    {
        Log::channel('activity')->info('Ticket work started', $this->context($event->ticket, $event->actor));
    }

    // How many files came with the resolution — never their names or the notes text.
    public function handleTicketResolved(TicketResolved $event): void
    {
        Log::channel('activity')->info('Ticket resolved', [
            ...$this->context($event->ticket, $event->actor),
            'attachments' => $event->attachmentCount,
        ]);
    }

    /**
     * $actor is null when the system acted on its own (auto-assign).
     *
     * @return array{ticket_id: int, ticket_number: string, actor_id: ?int, actor_name: string}
     */
    private function context(Ticket $ticket, ?User $actor): array
    {
        return [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->username() ?? 'system',
        ];
    }
}

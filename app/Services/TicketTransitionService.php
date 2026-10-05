<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Moves a ticket from one status to another with its audit-trail entry, as one
 * transaction. Every workflow step (approve, decline, resubmit — later assign, resolve,
 * reopen) goes through here so status and history can never disagree.
 */
class TicketTransitionService
{
    public function __construct(private readonly TicketRepository $tickets) {}

    /**
     * @param  array<string, mixed>  $changes  other ticket fields to save with the status
     * @param  (Closure(Ticket): void)|null  $during  extra writes that must commit with the move
     *
     * @throws AuthorizationException when the ticket left $from before this request got here
     */
    public function move(
        Ticket $ticket,
        User $actor,
        TicketAction $action,
        TicketStatus $from,
        TicketStatus $to,
        ?string $comment = null,
        array $changes = [],
        ?Closure $during = null,
    ): Ticket {
        return DB::transaction(function () use ($ticket, $actor, $action, $from, $to, $comment, $changes, $during) {
            // The policy checked the status when the request arrived; re-check under a row
            // lock so a second tab or a colleague acting at the same moment can't double-move it.
            $locked = $this->tickets->lockForUpdate($ticket);

            if ($locked->status !== $from->value) {
                throw new AuthorizationException('This ticket was updated by someone else a moment ago. Please review it again.');
            }

            $this->tickets->update($locked, [...$changes, 'status' => $to->value]);

            if ($during) {
                $during($locked);
            }

            $this->tickets->logHistory($locked, [
                'actor_id' => $actor->id,
                'actor_role' => $actor->getRoleNames()->first(),
                'action' => $action->value,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'comment' => $comment,
            ]);

            return $locked;
        });
    }
}

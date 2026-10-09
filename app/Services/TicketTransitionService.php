<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Repositories\TicketRepository;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Moves a ticket from one status to another with its audit-trail entry, as one
 * transaction. Every workflow step (approve, decline, resubmit, assign, reassign, start work, resolve —
 * later reopen) goes through here so status and history can never disagree.
 */
class TicketTransitionService
{
    // actor_role recorded when the system acts on its own (e.g. auto-assign), with actor_id null.
    public const SYSTEM_ROLE = 'System';

    public function __construct(private readonly TicketRepository $tickets) {}

    /**
     * @param  User|null  $actor  null when the system acts on its own (auto-assign)
     * @param  array<string, mixed>  $changes  other ticket fields to save with the status
     * @param  (Closure(Ticket, TicketStatusHistory): void)|null  $during  extra writes that must commit with
     *                                                                   the move, e.g. files attached to this step
     * @param  array<string, mixed>  $meta  structured detail for the history row, e.g. from/to assignee
     *
     * @throws AuthorizationException when the ticket left $from before this request got here
     */
    public function move(
        Ticket $ticket,
        ?User $actor,
        TicketAction $action,
        TicketStatus $from,
        TicketStatus $to,
        ?string $comment = null,
        array $changes = [],
        ?Closure $during = null,
        array $meta = [],
    ): Ticket {
        return DB::transaction(function () use ($ticket, $actor, $action, $from, $to, $comment, $changes, $during, $meta) {
            // The policy checked the status when the request arrived; re-check under a row
            // lock so a second tab or a colleague acting at the same moment can't double-move it.
            $locked = $this->tickets->lockForUpdate($ticket);

            if ($locked->status !== $from->value) {
                throw new AuthorizationException('This ticket was updated by someone else a moment ago. Please review it again.');
            }

            $this->tickets->update($locked, [...$changes, 'status' => $to->value]);

            $entry = $this->tickets->logHistory($locked, [
                'actor_id' => $actor?->id,
                'actor_role' => $actor ? $actor->getRoleNames()->first() : self::SYSTEM_ROLE,
                'action' => $action->value,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'comment' => $comment,
                'meta' => $meta ?: null,
            ]);

            // After the history row exists, so extra records (e.g. files) can point at this step.
            if ($during) {
                $during($locked, $entry);
            }

            return $locked;
        });
    }
}

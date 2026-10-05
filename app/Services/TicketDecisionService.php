<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketApproved;
use App\Events\TicketDeclined;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAwaitingAssignment;
use App\Notifications\TicketReturned;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Notification;

/**
 * Flow 3: the line manager's approve / decline decision.
 */
class TicketDecisionService
{
    public function __construct(
        private readonly TicketTransitionService $transitions,
        private readonly UserRepository $users,
    ) {}

    public function approve(Ticket $ticket, User $manager): Ticket
    {
        $ticket = $this->transitions->move(
            $ticket, $manager, TicketAction::Approved,
            from: TicketStatus::PendingLineManagerApproval,
            to: TicketStatus::PendingAssignment,
        );

        TicketApproved::dispatch($ticket, $manager);
        Notification::send($this->users->withRole(RoleName::HeadOfServiceManagement), new TicketAwaitingAssignment($ticket));

        return $ticket;
    }

    // The ticket goes back to the requester with the comment — it is NOT closed.
    public function decline(Ticket $ticket, User $manager, string $comment): Ticket
    {
        $ticket = $this->transitions->move(
            $ticket, $manager, TicketAction::Rejected,
            from: TicketStatus::PendingLineManagerApproval,
            to: TicketStatus::Returned,
            comment: $comment,
        );

        TicketDeclined::dispatch($ticket, $manager, $comment);
        $ticket->requester->notify(new TicketReturned($ticket, $comment));

        return $ticket;
    }
}

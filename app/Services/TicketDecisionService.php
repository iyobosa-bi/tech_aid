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
    // Posted to the conversation when the line manager approves without writing a comment.
    public const DEFAULT_APPROVAL_COMMENT = 'Approved by me. No comments';

    public function __construct(
        private readonly TicketTransitionService $transitions,
        private readonly UserRepository $users,
    ) {}
    // The comment is saved on the approval's history entry, so it shows in the conversation.
    public function approve(Ticket $ticket, User $manager, ?string $comment = null): Ticket
    {
        $ticket = $this->transitions->move(
            $ticket, $manager, TicketAction::Approved,
            from: TicketStatus::PendingLineManagerApproval,
            to: TicketStatus::PendingAssignment,
            comment: filled($comment) ? $comment : self::DEFAULT_APPROVAL_COMMENT,
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

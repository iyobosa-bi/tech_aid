<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketResubmitted;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAwaitingApproval;
use Illuminate\Support\Arr;

/**
 * Flow 4: the requester edits a returned ticket and sends it back to their line manager.
 */
class TicketResubmissionService
{
    public function __construct(
        private readonly TicketTransitionService $transitions,
        private readonly TicketUploadService $uploads,
    ) {}

    /**
     * @param  array{title: string, description: string, category: string, priority: string, note?: ?string, attachments?: list<string>}  $data
     */
    public function resubmit(Ticket $ticket, User $requester, array $data): Ticket
    {
        $uploadIds = $data['attachments'] ?? [];
        $uploads = $this->uploads->resolveAll($uploadIds);

        $ticket = $this->transitions->move(
            $ticket, $requester, TicketAction::Resubmitted,
            from: TicketStatus::Returned,
            to: TicketStatus::PendingLineManagerApproval,
            comment: $data['note'] ?? null,
            changes: Arr::only($data, ['title', 'description', 'category', 'priority']),
            during: fn (Ticket $locked) => $this->uploads->attachAll($locked, $requester, $uploads),
        );

        $this->uploads->forgetAll($uploadIds);

        TicketResubmitted::dispatch($ticket, $requester);
        $ticket->lineManager->notify(new TicketAwaitingApproval($ticket, resubmitted: true));

        return $ticket;
    }
}

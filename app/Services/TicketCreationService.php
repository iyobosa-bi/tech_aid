<?php

namespace App\Services;

use App\Enums\TicketAction;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAwaitingApproval;
use App\Notifications\TicketStatusUpdated;
use App\Repositories\TicketRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TicketCreationService
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketUploadService $uploads,
    ) {}

    /**
     * Flow 2: raise a ticket, route it to the requester's line manager, and
     * log the first audit-trail entry.
     *
     * @param  array{title: string, description: string, category: string, priority: string, attachments?: list<string>}  $data
     */
    public function create(User $requester, array $data): Ticket
    {
        // First thing, so attempts that fail below (no line manager, expired upload) are recorded too.
        Log::channel('activity')->info('Ticket creation attempted', [
            'user_id' => $requester->id,
            'username' => $requester->username(),
        ]);

        if (! $requester->line_manager_id) {
            throw ValidationException::withMessages([
                'line_manager' => 'You have no line manager assigned, so this ticket cannot be routed for approval. Please contact an administrator.',
            ]);
        }

        $uploadIds = $data['attachments'] ?? [];
        $uploads = $this->uploads->resolveAll($uploadIds);

        $ticket = DB::transaction(function () use ($requester, $data, $uploads) {
            $ticket = $this->tickets->create([
                'requester_id' => $requester->id,
                'line_manager_id' => $requester->line_manager_id,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => TicketStatus::PendingLineManagerApproval->value,
            ]);

            $this->uploads->attachAll($ticket, $requester, $uploads);

            $this->tickets->logHistory($ticket, [
                'actor_id' => $requester->id,
                'actor_role' => $requester->getRoleNames()->first(),
                'action' => TicketAction::Created->value,
                'from_status' => null,
                'to_status' => TicketStatus::PendingLineManagerApproval->value,
            ]);

            return $ticket;
        });

        $this->uploads->forgetAll($uploadIds);

        // After the commit, so listeners (e.g. LogTicketActivity) only ever see saved tickets.
        TicketCreated::dispatch($ticket, $requester);

        $ticket->lineManager->notify(new TicketAwaitingApproval($ticket));
        $requester->notify(new TicketStatusUpdated($ticket, TicketStatus::PendingLineManagerApproval));

        return $ticket;
    }
}

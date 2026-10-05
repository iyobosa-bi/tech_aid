<?php

namespace App\Repositories;

use App\Enums\TicketAction;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TicketRepository
{
    public const SORTABLE = ['id', 'requester', 'category', 'priority', 'status', 'created_at'];

    public const PER_PAGE = 15;

    /**
     * @param  array{search: string, status: ?string, sort: string, direction: string}  $filters
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginateVisibleTo(User $user, array $filters): LengthAwarePaginator
    {
        $query = $this->visibleTo($user);

        if ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';

            $query->where(fn (Builder $q) => $q
                ->whereLike('ticket_number', $term)
                ->orWhereLike('title', $term)
                ->orWhereHas('requester', fn (Builder $r) => $r->whereLike('name', $term)));
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        match ($filters['sort']) {
            'requester' => $query->orderBy(
                User::withTrashed()->select('name')->whereColumn('users.id', 'tickets.requester_id'),
                $filters['direction'],
            ),
            // By severity, not alphabetically. Direction is whitelisted to asc|desc upstream.
            'priority' => $query->orderByRaw(
                "CASE priority WHEN 'high' THEN 3 WHEN 'medium' THEN 2 WHEN 'low' THEN 1 ELSE 0 END {$filters['direction']}"
            ),
            default => $query->orderBy($filters['sort'], $filters['direction']),
        };

        // Tie-breaker so rows with equal sort values don't shuffle between pages.
        return $query->orderBy('id', 'desc')->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * The newest tickets on the user's Tickets page, for the dashboard's Recent Tickets.
     *
     * @return Collection<int, Ticket>
     */
    public function recentVisibleTo(User $user, int $limit): Collection
    {
        return $this->visibleTo($user)->latest()->orderBy('id', 'desc')->limit($limit)->get();
    }

    /**
     * Everything the ticket page shows, in display order: history oldest first.
     */
    public function loadForDetail(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'requester', 'lineManager', 'assignedTo',
            'attachments' => fn ($query) => $query->orderBy('id'),
            'statusHistory' => fn ($query) => $query->with('actor')->orderBy('created_at')->orderBy('id'),
        ]);
    }

    /**
     * Re-reads the ticket and locks its row until the surrounding transaction ends, so two
     * simultaneous actions (e.g. approve in two tabs) can't both act on the same status.
     */
    public function lockForUpdate(Ticket $ticket): Ticket
    {
        return Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Ticket $ticket, array $attributes): Ticket
    {
        $ticket->update($attributes);

        return $ticket;
    }

    /**
     * People on the ticket — requester, line manager, assignee, assigner and anyone who has
     * joined the conversation — other than $except. Deactivated users are left out.
     *
     * @return Collection<int, User>
     */
    public function participantsExcept(Ticket $ticket, User $except): Collection
    {
        $ids = collect([$ticket->requester_id, $ticket->line_manager_id, $ticket->assigned_to_id, $ticket->assigned_by_id])
            ->merge($ticket->statusHistory()->where('action', TicketAction::Commented->value)->pluck('actor_id'))
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $except->id);

        return User::query()->whereIn('id', $ids)->get();
    }

    // Same visibility rule (Ticket::scopeVisibleTo) and columns for every ticket listing.
    private function visibleTo(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)->with(['requester:id,name', 'assignedTo:id,name']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Ticket
    {
        // ticket_number is derived from the id, which only exists after the
        // insert — a unique placeholder satisfies the NOT NULL/unique columns
        // until then, keeping numbering race-free without a separate sequence.
        $ticket = Ticket::create([...$attributes, 'ticket_number' => (string) Str::uuid()]);

        $ticket->update(['ticket_number' => 'TA-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);

        return $ticket;
    }
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addAttachment(Ticket $ticket, array $attributes): TicketAttachment
    {
        return $ticket->attachments()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logHistory(Ticket $ticket, array $attributes): TicketStatusHistory
    {
        return $ticket->statusHistory()->create($attributes);
    }
}

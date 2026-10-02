<?php

namespace App\Repositories;

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

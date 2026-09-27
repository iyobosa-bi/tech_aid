<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistory;
use Illuminate\Support\Str;

class TicketRepository
{
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

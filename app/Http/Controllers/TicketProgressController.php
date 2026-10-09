<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Flow 7: the assigned Application Support person starts work (assigned → in_progress).
 * TicketPolicy::startProgress makes sure it's their ticket.
 */
class TicketProgressController extends Controller
{
    public function store(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('startProgress', $ticket);

        $ticket = $service->startProgress($ticket, $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "You've started work on {$ticket->ticket_number}. {$ticket->requester->name} has been told it's in progress.");
    }
}

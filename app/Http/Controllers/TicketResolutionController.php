<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveTicketRequest;
use App\Models\Ticket;
use App\Services\TicketResolutionService;
use Illuminate\Http\RedirectResponse;

/**
 * Resolve a ticket with notes — Head of Service Management directly (Flow 5), and later the
 * assigned support person (Flow 7). ResolveTicketRequest authorises via TicketPolicy::resolve.
 */
class TicketResolutionController extends Controller
{
    public function store(ResolveTicketRequest $request, Ticket $ticket, TicketResolutionService $resolutions): RedirectResponse
    {
        $ticket = $resolutions->resolve($ticket, $request->user(), $request->validated('resolution_notes'));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} resolved. {$ticket->requester->name} has been notified.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeclineTicketRequest;
use App\Models\Ticket;
use App\Services\TicketDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Flow 3: the line manager approves or declines a ticket from its detail page.
 */
class TicketDecisionController extends Controller
{
    public function approve(Request $request, Ticket $ticket, TicketDecisionService $decisions): RedirectResponse
    {
        $this->authorize('approve', $ticket);

        $ticket = $decisions->approve($ticket, $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} approved. It's now with Head of Service Management for assignment.");
    }

    // DeclineTicketRequest authorises (TicketPolicy::decline) and requires the comment.
    public function decline(DeclineTicketRequest $request, Ticket $ticket, TicketDecisionService $decisions): RedirectResponse
    {
        $ticket = $decisions->decline($ticket, $request->user(), $request->validated('comment'));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} returned to {$ticket->requester->name} with your comment.");
    }
}

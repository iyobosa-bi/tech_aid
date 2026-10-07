<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveTicketRequest;
use App\Http\Requests\DeclineTicketRequest;
use App\Models\Ticket;
use App\Services\TicketDecisionService;
use Illuminate\Http\RedirectResponse;

/**
 * Flow 3: the line manager approves or declines a ticket from its detail page.
 */
class TicketDecisionController extends Controller
{
    // ApproveTicketRequest authorises (TicketPolicy::approve) and validates the optional comment.
    public function approve(ApproveTicketRequest $request, Ticket $ticket, TicketDecisionService $decisions): RedirectResponse
    {
        $ticket = $decisions->approve($ticket, $request->user(), $request->validated('comment'));

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

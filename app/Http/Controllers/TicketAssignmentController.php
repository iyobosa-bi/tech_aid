<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\ReassignTicketRequest;
use App\Models\Ticket;
use App\Services\SupportAssignmentService;
use Illuminate\Http\RedirectResponse;

/**
 * Flows 5–6: Head of Service Management assigns or reassigns a ticket from its page.
 * The form requests authorise (TicketPolicy::assign / reassign) and check the chosen person.
 */
class TicketAssignmentController extends Controller
{
    public function assign(AssignTicketRequest $request, Ticket $ticket, SupportAssignmentService $assignments): RedirectResponse
    {
        $assignee = $request->assignee();
        $ticket = $assignments->assign($ticket, $request->user(), $assignee, $request->validated('note'));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} assigned to {$assignee->name}.");
    }

    public function reassign(ReassignTicketRequest $request, Ticket $ticket, SupportAssignmentService $assignments): RedirectResponse
    {
        $assignee = $request->assignee();
        $ticket = $assignments->reassign($ticket, $request->user(), $assignee, $request->validated('note'));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} reassigned to {$assignee->name}.");
    }
}

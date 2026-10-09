<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Resolve a ticket with notes and optional files — the assigned support person (Flow 7), or Head
 * of Service Management directly (Flow 5). ResolveTicketRequest authorises via TicketPolicy::resolve.
 */
class TicketResolutionController extends Controller
{
    public function store(ResolveTicketRequest $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        try {
            $ticket = $service->resolve($ticket, $request->user(), $request->validated('resolution_notes'), $request->uploadIds());
        } catch (ValidationException $e) {
            // An expired upload: report it in the resolve modal's own error bag so the modal reopens.
            throw $e->errorBag('resolve');
        }

        $files = count($request->uploadIds());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} resolved"
                .($files ? " with {$files} ".Str::plural('file', $files) : '')
                .". {$ticket->requester->name} has been notified.");
    }
}

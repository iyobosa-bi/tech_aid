<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use App\Services\TicketCommentService;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    // StoreTicketCommentRequest authorises (TicketPolicy::comment) and validates the message.
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $comments): RedirectResponse
    {
        $comments->post($ticket, $request->user(), $request->validated('body'));

        // Land back on the conversation, where the new message now shows.
        return redirect()->to(route('tickets.show', $ticket).'#conversation');
    }
}

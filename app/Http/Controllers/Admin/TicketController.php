<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListTicketsRequest;
use App\Models\Ticket;
use App\Repositories\TicketRepository;
use Illuminate\View\View;

/**
 * Admin → All tickets: every ticket in the same searchable, sortable list as the Tickets page,
 * with a View action. Admins can open any ticket read-only (TicketPolicy::view); they can't act
 * on it or post in its conversation.
 */
class TicketController extends Controller
{
    public function index(ListTicketsRequest $request, TicketRepository $tickets): View
    {
        $this->authorize('viewAll', Ticket::class);

        $filters = $request->filters();

        $data = [
            'tickets' => $tickets->paginateVisibleTo($request->user(), $filters),
            'filters' => $filters,
            'showRequester' => true,
            'showActions' => true,
            'listUrl' => route('admin.tickets'),
        ];

        // Live search swaps only the results region, so skip the full layout.
        if ($request->ajax()) {
            return view('tickets.partials.results', $data);
        }

        return view('tickets.index', [
            ...$data,
            'statuses' => TicketStatus::cases(),
            'heading' => 'All Tickets',
            'pageTitle' => 'All tickets',
        ]);
    }
}

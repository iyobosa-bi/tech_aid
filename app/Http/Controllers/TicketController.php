<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Enums\TicketAction;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use App\Services\TicketCreationService;
use App\Services\TicketResubmissionService;
use App\Services\TicketUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(ListTicketsRequest $request, TicketRepository $tickets): View
    {
        $filters = $request->filters();

        $data = [
            'tickets' => $tickets->paginateVisibleTo($request->user(), $filters),
            'filters' => $filters,
            // Requesters only see their own tickets, so the column would just repeat their name.
            'showRequester' => $request->user()->handlesTickets(),
        ];

        // Live search swaps only the results region, so skip the full layout.
        if ($request->ajax()) {
            return view('tickets.partials.results', $data);
        }

        return view('tickets.index', [
            ...$data,
            'statuses' => TicketStatus::cases(),
            'heading' => $this->listHeading($request->user()),
        ]);
    }

    public function show(Ticket $ticket, TicketRepository $tickets): View
    {
        $this->authorize('view', $ticket);

        $ticket = $tickets->loadForDetail($ticket);
        $history = $ticket->statusHistory;

        return view('tickets.show', [
            'ticket' => $ticket,
            // Messages plus any decision that carried a comment (e.g. a decline reason).
            'conversation' => $history->filter->hasMessage()->values(),
            // Status changes only; plain messages live in the conversation.
            'timeline' => $history->reject->isComment()->values(),
            'returnedBy' => $ticket->status === TicketStatus::Returned->value
                ? $history->last(fn ($entry) => $entry->action === TicketAction::Rejected->value)
                : null,
        ]);
    }

    public function create(Request $request, TicketUploadService $uploads): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.form', $this->formData($request, $uploads));
    }

    public function store(StoreTicketRequest $request, TicketCreationService $service): RedirectResponse
    {
        $ticket = $service->create($request->user(), $request->validated());

        return redirect()
            ->route('dashboard')
            ->with('success', "Ticket {$ticket->ticket_number} submitted. It's now awaiting approval from {$ticket->lineManager->name}.");
    }

    // Flow 4: the same form as create(), pre-filled, for a ticket returned to its requester.
    public function edit(Request $request, Ticket $ticket, TicketUploadService $uploads, TicketRepository $tickets): View
    {
        $this->authorize('resubmit', $ticket);

        return view('tickets.form', $this->formData($request, $uploads, $tickets->loadForDetail($ticket)));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketResubmissionService $service): RedirectResponse
    {
        $ticket = $service->resubmit($ticket, $request->user(), $request->validated());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} resubmitted. It's back with {$ticket->lineManager->name} for approval.");
    }

    /**
     * Shared by create and edit. $ticket is null when raising a new ticket.
     *
     * @return array<string, mixed>
     */
    private function formData(Request $request, TicketUploadService $uploads, ?Ticket $ticket = null): array
    {
        $attachments = $ticket?->attachments ?? collect();

        // After a failed validation, re-show files already uploaded so they aren't lost.
        $existingUploads = collect($request->old('attachments', []))
            ->map(fn ($id) => [$id, is_string($id) ? $uploads->find($id) : null])
            ->filter(fn ($pair) => $pair[1] !== null)
            ->map(fn ($pair) => [
                'source' => $pair[0],
                'options' => [
                    'type' => 'limbo',
                    'file' => [
                        'name' => $pair[1]['original_filename'],
                        'size' => $pair[1]['size'],
                        'type' => $pair[1]['mime_type'],
                    ],
                ],
            ])
            ->values()
            ->all();

        return [
            'ticket' => $ticket,
            'categories' => TicketCategory::cases(),
            'priorities' => TicketPriority::cases(),
            'lineManager' => $ticket ? $ticket->lineManager : $request->user()->lineManager,
            'attachments' => $attachments,
            // New files allowed on this form: the overall limit minus files already on the ticket.
            'maxAttachments' => max(0, StoreTicketRequest::MAX_ATTACHMENTS - $attachments->count()),
            'existingUploads' => $existingUploads,
            'returnedBy' => $ticket?->statusHistory->last(fn ($entry) => $entry->action === TicketAction::Rejected->value),
        ];
    }

    // Mirrors Ticket::scopeVisibleTo: names the slice of tickets this user is looking at.
    private function listHeading(User $user): string
    {
        return match (true) {
            $user->checkPermissionTo(PermissionName::AssignTickets) => 'All Tickets',
            $user->checkPermissionTo(PermissionName::ApproveTickets) => 'Team Tickets',
            $user->checkPermissionTo(PermissionName::ResolveTickets) => 'Assigned Tickets',
            default => 'My Tickets',
        };
    }
}

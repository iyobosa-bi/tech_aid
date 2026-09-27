<?php

namespace App\Http\Controllers;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\TicketCreationService;
use App\Services\TicketUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function create(Request $request, TicketUploadService $uploads): View
    {
        $this->authorize('create', Ticket::class);

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

        return view('tickets.create', [
            'categories' => TicketCategory::cases(),
            'priorities' => TicketPriority::cases(),
            'lineManager' => $request->user()->lineManager,
            'maxAttachments' => StoreTicketRequest::MAX_ATTACHMENTS,
            'existingUploads' => $existingUploads,
        ]);
    }

    public function store(StoreTicketRequest $request, TicketCreationService $service): RedirectResponse
    {
        $ticket = $service->create($request->user(), $request->validated());

        return redirect()
            ->route('dashboard')
            ->with('success', "Ticket {$ticket->ticket_number} submitted. It's now awaiting approval from {$ticket->lineManager->name}.");
    }
}

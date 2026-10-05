<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a ticket's files from private storage — never a public URL. The routes use
 * scoped bindings, so {attachment} must belong to {ticket} or the request 404s.
 */
class TicketAttachmentController extends Controller
{
    // Quick view: shown in the browser (images, PDFs only) instead of downloaded.
    public function show(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);
        abort_unless($attachment->isPreviewable() && Storage::exists($attachment->file_path), 404);

        return Storage::response($attachment->file_path, $attachment->original_filename, [
            'Content-Type' => $attachment->mime_type,
            // Stops the browser from second-guessing the type and running a disguised file.
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $ticket);
        abort_unless(Storage::exists($attachment->file_path), 404);

        return Storage::download($attachment->file_path, $attachment->original_filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

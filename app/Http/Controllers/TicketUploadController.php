<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * FilePond's async process/revert endpoints. The upload runs ahead of the
 * form submit so FilePond can show real upload progress; the form itself is
 * still a traditional POST + redirect.
 *
 * Two forms use them, each with its own permission check: the ticket form
 * (anyone who may raise tickets) and the resolve form (only someone who may
 * resolve that particular ticket — its assigned support person, or Head of
 * Service Management resolving directly).
 */
class TicketUploadController extends Controller
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

    public const MAX_KILOBYTES = 10240;

    public function store(Request $request, TicketUploadService $uploads): Response
    {
        $this->authorize('create', Ticket::class);

        return $this->stash($request, $uploads);
    }

    public function destroy(Request $request, TicketUploadService $uploads): Response
    {
        $this->authorize('create', Ticket::class);

        return $this->discard($request, $uploads);
    }

    // Files for a resolution (Flows 5 and 7).
    public function storeForResolution(Request $request, Ticket $ticket, TicketUploadService $uploads): Response
    {
        $this->authorize('resolve', $ticket);

        return $this->stash($request, $uploads);
    }

    public function destroyForResolution(Request $request, Ticket $ticket, TicketUploadService $uploads): Response
    {
        $this->authorize('resolve', $ticket);

        return $this->discard($request, $uploads);
    }

    private function stash(Request $request, TicketUploadService $uploads): Response
    {
        // FilePond posts the file under the input's own name ("attachments[]"),
        // but it ALSO always posts a JSON metadata part under that exact same
        // field name first (even when there's no real metadata - it's an
        // unconditional `{}`). $request->validate()/all() would use
        // array_replace_recursive() to merge plain POST fields back on top of
        // files at colliding array indices, so the metadata string wins over
        // the real file at attachments.0. Validating $request->file() directly
        // (Symfony's file bag only, no POST fields mixed in) sidesteps that.
        Validator::make(
            ['attachments' => $request->file('attachments')],
            [
                'attachments' => ['required', 'array', 'size:1'],
                'attachments.*' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS)],
            ],
            [
                'attachments.*.max' => 'The file may not be larger than 10MB.',
                'attachments.*.mimes' => 'Only JPG, PNG, PDF and Word documents are allowed.',
            ],
        )->validate();

        $id = $uploads->stash($request->file('attachments')[0]);

        return response($id, 200, ['Content-Type' => 'text/plain']);
    }

    // Upload ids live in the uploader's session, so only their own files can be removed.
    private function discard(Request $request, TicketUploadService $uploads): Response
    {
        $uploads->discard(trim($request->getContent()));

        return response()->noContent();
    }
}

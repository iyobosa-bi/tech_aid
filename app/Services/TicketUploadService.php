<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Holds FilePond's async uploads in a temporary area until the ticket form is
 * submitted. Upload ids are tracked in the uploader's session, so one user can
 * never attach (or revert) a file another user uploaded.
 */
class TicketUploadService
{
    private const SESSION_KEY = 'ticket_uploads';

    private const TEMP_DIRECTORY = 'tmp/ticket-uploads';

    public function __construct(private readonly TicketRepository $tickets) {}

    public function stash(UploadedFile $file): string
    {
        $id = (string) Str::uuid();

        session()->put(self::SESSION_KEY.'.'.$id, [
            'path' => $file->store(self::TEMP_DIRECTORY),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return $id;
    }

    public function discard(string $id): void
    {
        $upload = $this->find($id);

        if ($upload) {
            Storage::delete($upload['path']);
            session()->forget(self::SESSION_KEY.'.'.$id);
        }
    }

    /**
     * @return array{path: string, original_filename: string, mime_type: string, size: int}|null
     */
    public function find(string $id): ?array
    {
        return session()->get(self::SESSION_KEY.'.'.$id);
    }

    public function forget(string $id): void
    {
        session()->forget(self::SESSION_KEY.'.'.$id);
    }

    /**
     * Looks up every submitted upload id, before anything is saved — so an expired upload
     * fails the whole form instead of leaving a half-saved ticket.
     *
     * @param  list<string>  $ids
     * @return list<array{path: string, original_filename: string, mime_type: string, size: int}>
     *
     * @throws ValidationException
     */
    public function resolveAll(array $ids): array
    {
        return array_map(function (string $id) {
            $upload = $this->find($id);

            if (! $upload || ! Storage::exists($upload['path'])) {
                throw ValidationException::withMessages([
                    'attachments' => 'One of your attachments has expired or is no longer available. Please remove it and upload it again.',
                ]);
            }

            return $upload;
        }, $ids);
    }

    /**
     * Moves resolved uploads into the ticket's folder and records them. Call inside the
     * ticket's DB::transaction so the rows and the ticket change commit together.
     * $step is the workflow step they came with (e.g. the resolution); null when raising the ticket.
     *
     * @param  list<array{path: string, original_filename: string, mime_type: string, size: int}>  $uploads
     */
    public function attachAll(Ticket $ticket, User $uploader, array $uploads, ?TicketStatusHistory $step = null): void
    {
        foreach ($uploads as $upload) {
            $path = 'tickets/'.$ticket->id.'/'.basename($upload['path']);
            Storage::move($upload['path'], $path);

            $this->tickets->addAttachment($ticket, [
                'uploaded_by_id' => $uploader->id,
                'status_history_id' => $step?->id,
                'file_path' => $path,
                'original_filename' => $upload['original_filename'],
                'mime_type' => $upload['mime_type'],
                'size' => $upload['size'],
            ]);
        }
    }

    /**
     * After a failed form submit, FilePond re-shows the files already uploaded so they aren't
     * lost ("limbo" files: on the server, not yet attached). Unknown or foreign ids are skipped.
     *
     * @param  array<mixed>  $ids  usually old('attachments')
     * @return list<array{source: string, options: array{type: string, file: array{name: string, size: int, type: string}}}>
     */
    public function filePondFiles(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => [$id, is_string($id) ? $this->find($id) : null])
            ->filter(fn (array $pair) => $pair[1] !== null)
            ->map(fn (array $pair) => [
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
    }

    /**
     * @param  list<string>  $ids
     */
    public function forgetAll(array $ids): void
    {
        foreach ($ids as $id) {
            $this->forget($id);
        }
    }
}

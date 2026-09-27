<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Holds FilePond's async uploads in a temporary area until the ticket form is
 * submitted. Upload ids are tracked in the uploader's session, so one user can
 * never attach (or revert) a file another user uploaded.
 */
class TicketUploadService
{
    private const SESSION_KEY = 'ticket_uploads';

    private const TEMP_DIRECTORY = 'tmp/ticket-uploads';

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
}

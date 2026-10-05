<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    /**
     * Types the browser can show safely in the quick-view preview. Word files have no
     * in-browser viewer (and the bank's files mustn't go to an outside one), so they
     * are download-only. mime_type is detected from the file's content on upload.
     */
    public const PREVIEWABLE_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    protected $fillable = [
        'ticket_id',
        'uploaded_by_id',
        'file_path',
        'original_filename',
        'mime_type',
        'size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, self::PREVIEWABLE_TYPES, true);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    // e.g. "820 B", "14.2 KB", "1.4 MB". (Number::fileSize() needs PHP's intl extension, which isn't installed.)
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return ($unit === 0 ? (int) $size : round($size, 1)).' '.$units[$unit];
    }

    // e.g. "PDF", "DOCX" — for the file badge.
    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }
}

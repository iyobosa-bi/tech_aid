<?php

namespace App\Enums;

/**
 * ticket_status_history.action values, per docs/06-data-model.md.
 */
enum TicketAction: string
{
    case Created = 'created';
    case Approved = 'approved';
    case Rejected = 'rejected'; // Line Manager decline — the ticket moves to `returned`
    case Resubmitted = 'resubmitted';
    case Assigned = 'assigned';
    case Reassigned = 'reassigned';
    case Started = 'started';
    case Commented = 'commented';
    case Resolved = 'resolved';
    case Reopened = 'reopened';
    case Rated = 'rated';

    // As shown in the ticket's status timeline.
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Ticket raised',
            self::Approved => 'Approved',
            self::Rejected => 'Declined',
            self::Resubmitted => 'Resubmitted',
            self::Assigned => 'Assigned',
            self::Reassigned => 'Reassigned',
            self::Started => 'Work started',
            self::Commented => 'Commented',
            self::Resolved => 'Resolved',
            self::Reopened => 'Reopened',
            self::Rated => 'Rated',
        };
    }

    // Lucide icon name.
    public function icon(): string
    {
        return match ($this) {
            self::Created => 'plus',
            self::Approved => 'check',
            self::Rejected => 'undo-2',
            self::Resubmitted => 'send',
            self::Assigned, self::Reassigned => 'user-check',
            self::Started => 'play',
            self::Commented => 'message-square',
            self::Resolved => 'circle-check',
            self::Reopened => 'rotate-ccw',
            self::Rated => 'star',
        };
    }

    // Icon-dot colours, drawn from the status-badge palette in design/style-notes.md.
    public function tone(): string
    {
        return match ($this) {
            self::Approved => 'bg-teal/15 text-teal-700',
            self::Rejected, self::Rated => 'bg-amber-100 text-amber-700',
            self::Resolved => 'bg-green-100 text-green-700',
            self::Reopened => 'bg-red-100 text-red-700',
            self::Commented => 'bg-gray-100 text-gray-600',
            default => 'bg-brand/10 text-brand',
        };
    }
}

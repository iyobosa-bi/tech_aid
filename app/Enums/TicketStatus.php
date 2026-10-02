<?php

namespace App\Enums;

enum TicketStatus: string
{
    case PendingLineManagerApproval = 'pending_line_manager_approval';
    case Returned = 'returned';
    case PendingAssignment = 'pending_assignment';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    /**
     * Statuses still waiting on someone (dashboard "Open Tickets").
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::PendingLineManagerApproval, self::Returned, self::PendingAssignment, self::Assigned, self::InProgress];
    }

    /**
     * Statuses where Application Support is actively working the ticket.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Assigned, self::InProgress];
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingLineManagerApproval => 'Pending Approval',
            self::Returned => 'Returned',
            self::PendingAssignment => 'Pending Assignment',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
        };
    }
}

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
}

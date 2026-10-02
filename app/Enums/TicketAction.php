<?php

namespace App\Enums;

/**
 * ticket_status_history.action values, per docs/06-data-model.md.
 */
enum TicketAction: string
{
    case Created = 'created';
    case Approved = 'approved';
    case Rejected = 'rejected'; // Line Manager reject — the ticket moves to `returned`
    case Resubmitted = 'resubmitted';
    case Assigned = 'assigned';
    case Reassigned = 'reassigned';
    case Started = 'started';
    case Commented = 'commented';
    case Resolved = 'resolved';
    case Reopened = 'reopened';
    case Rated = 'rated';
}

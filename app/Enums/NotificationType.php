<?php

namespace App\Enums;

/**
 * The kinds of in-app notification. The value is stored in notifications.type (each
 * notification's databaseType()), so filtering is a plain column match and renaming a
 * notification class can never orphan old rows. The label is the bell's tag chip.
 */
enum NotificationType: string
{
    case ApprovalRequested = 'approval-requested';
    case AssignmentRequested = 'assignment-requested';
    case Returned = 'ticket-returned';
    case Message = 'message';
    case StatusUpdate = 'status-update';

    public function label(): string
    {
        return match ($this) {
            self::ApprovalRequested => 'Approval',
            self::AssignmentRequested => 'Assignment',
            self::Returned => 'Returned',
            self::Message => 'Message',
            self::StatusUpdate => 'Status update',
        };
    }
}

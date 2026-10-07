<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Notifications used to store their PHP class name in `type`; they now store a short
 * App\Enums\NotificationType value (each notification's databaseType()). Converts the
 * rows written before that change so they get the right tag chip and filter.
 */
return new class extends Migration
{
    private const TYPES = [
        'App\Notifications\TicketAwaitingApproval' => 'approval-requested',
        'App\Notifications\TicketAwaitingAssignment' => 'assignment-requested',
        'App\Notifications\TicketReturned' => 'ticket-returned',
        'App\Notifications\TicketMessagePosted' => 'message',
    ];

    public function up(): void
    {
        foreach (self::TYPES as $class => $type) {
            DB::table('notifications')->where('type', $class)->update(['type' => $type]);
        }
    }

    public function down(): void
    {
        foreach (self::TYPES as $class => $type) {
            DB::table('notifications')->where('type', $type)->update(['type' => $class]);
        }
    }
};

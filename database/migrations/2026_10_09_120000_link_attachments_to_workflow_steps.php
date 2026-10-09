<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flow 7: files can be attached when a ticket is resolved. Each file now records the workflow
 * step it came with (e.g. the `resolved` history entry), so the ticket page can show resolution
 * files under the resolution. Null = attached when the ticket was raised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->foreignId('status_history_id')->nullable()->after('uploaded_by_id')
                ->constrained('ticket_status_history')->nullOnDelete();
            $table->index('status_history_id');
            // Postgres doesn't index foreign keys on its own; the ticket page loads files by ticket.
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
            $table->dropIndex(['status_history_id']);
            $table->dropConstrainedForeignId('status_history_id');
        });
    }
};

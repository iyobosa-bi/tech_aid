<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flows 5–6: assignment by Head of Service Management, or automatically (least busy).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Admin can take support staff out of the auto-assign bucket while they're away.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('on_leave')->default(false)->after('department');
        });

        // When the ticket last changed hands — the least-busy tie-break, and future metrics.
        // Postgres doesn't index foreign keys on its own; these back the list, dashboard and workload queries.
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('assigned_by_id');
            $table->index(['assigned_to_id', 'status']);
            $table->index('status');
            $table->index('requester_id');
            $table->index('line_manager_id');
        });

        // An automatic assignment has no person behind it: actor_id null, actor_role 'System'.
        Schema::table('ticket_status_history', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->change();
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_status_history', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
            $table->foreignId('actor_id')->nullable(false)->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['assigned_to_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['requester_id']);
            $table->dropIndex(['line_manager_id']);
            $table->dropColumn('assigned_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('on_leave');
        });
    }
};

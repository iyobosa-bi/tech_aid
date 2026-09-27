<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('line_manager_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('department')->nullable()->after('password');
            $table->softDeletes();
        });

        // A plain unique index blocks reuse of a deleted user's email forever.
        // Swap it for a partial unique index that only applies to active rows
        // (see docs/06-data-model.md).
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });

        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['line_manager_id']);
            $table->dropColumn(['line_manager_id', 'department', 'deleted_at']);
        });
    }
};

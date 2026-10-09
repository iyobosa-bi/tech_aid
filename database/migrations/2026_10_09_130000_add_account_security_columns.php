<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin user management and the forgot-password flow.
 *
 * - users.deactivated_at: an Admin can switch an account off (and back on) without deleting it.
 *   Deactivated users can't sign in and are signed out on their next request.
 * - otp_codes.purpose: a password-reset code must never work as a login code, or the other way round.
 * - otp_codes.attempts: wrong guesses against a code; it's invalidated after OtpService::MAX_ATTEMPTS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('on_leave');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('purpose', 32)->default('login')->after('user_id');
            $table->unsignedTinyInteger('attempts')->default(0)->after('code');
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'purpose']);
            $table->dropColumn(['purpose', 'attempts']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deactivated_at');
        });
    }
};

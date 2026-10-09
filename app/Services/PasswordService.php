<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\PasswordResetCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Forgot password (email → 6-digit code → new password) and changing a password in Settings.
 *
 * Security rules this follows:
 * - Nothing tells a visitor whether an email is registered: sendResetCode() and verifyResetCode()
 *   look the same to the caller either way, and take about as long (OtpService::spendEquivalentTime).
 * - Codes: hashed, 3-minute expiry, single use, 5 wrong tries, purpose "password_reset" only
 *   (OtpService). Requests for codes are rate-limited by the route ('password-reset-codes').
 * - After a change: every unused code is cancelled, the remember-me token is rotated, other
 *   sessions are signed out (AuthenticateSession middleware notices the new password), and the
 *   user gets a "your password was changed" email. Sign-in still needs a login code (MFA).
 * - The activity log records who and from where — never the code or the password.
 */
class PasswordService
{
    // How long a verified code lets the person choose a new password.
    public const VERIFIED_FOR_MINUTES = 10;

    public function __construct(private readonly OtpService $otps) {}

    /**
     * Emails a reset code if an active account has this email. Returns the code (only so local
     * debug mode can print it), or null when there's no such account — callers must respond the same.
     */
    public function sendResetCode(string $email, string $ip): ?string
    {
        $user = $this->findAccount($email);

        if (! $user) {
            $this->otps->spendEquivalentTime();
            Log::channel('activity')->warning('Password reset requested for an unknown account', [
                'email_attempted' => Str::limit($email, 100, ''),
                'ip' => $ip,
            ]);

            return null;
        }

        $code = $this->otps->issue($user, OtpPurpose::PasswordReset);
        $user->notify(new PasswordResetCode($code));

        Log::channel('activity')->info('Password reset code sent', $this->identity($user, $ip));

        return $code;
    }

    /**
     * The account whose code this is, or null. Every failure (no account, wrong, expired, too
     * many tries) looks the same to the caller; the reason only goes to the activity log.
     */
    public function verifyResetCode(string $email, string $code, string $ip): ?User
    {
        $user = $this->findAccount($email);

        if (! $user) {
            $this->otps->spendEquivalentTime();
            Log::channel('activity')->warning('Password reset code rejected', ['reason' => 'unknown_account', 'ip' => $ip]);

            return null;
        }

        $check = $this->otps->verify($user, OtpPurpose::PasswordReset, $code);

        if (! $check->passed()) {
            Log::channel('activity')->warning('Password reset code rejected', [...$this->identity($user, $ip), 'reason' => $check->value]);

            return null;
        }

        Log::channel('activity')->info('Password reset code verified', $this->identity($user, $ip));

        return $user;
    }

    // Forgot password, after the code was verified.
    public function reset(User $user, string $password, string $ip): void
    {
        $this->changePassword($user, $password);

        event(new PasswordReset($user));
        $user->notify(new PasswordChanged(now(), viaReset: true));
        Log::channel('activity')->info('Password reset', $this->identity($user, $ip));
    }

    // Settings → Update password (the current password was already checked).
    public function change(User $user, string $password, string $ip): void
    {
        $this->changePassword($user, $password);

        $user->notify(new PasswordChanged(now()));
        Log::channel('activity')->info('Password changed', $this->identity($user, $ip));
    }

    private function changePassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password) {
            // The 'hashed' cast hashes it; a new remember token kills any "remember me" cookies.
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

            // Every purpose: a login code issued against the old password must die too.
            $this->otps->invalidate($user);
        });
    }

    // Active accounts only: deactivated and deleted users are treated as unknown.
    private function findAccount(string $email): ?User
    {
        return User::active()->whereRaw('lower(email) = ?', [Str::lower(trim($email))])->first();
    }

    /**
     * @return array{user_id: int, username: string, ip: string}
     */
    private function identity(User $user, string $ip): array
    {
        return ['user_id' => $user->id, 'username' => $user->username(), 'ip' => $ip];
    }
}

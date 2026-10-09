<?php

namespace App\Services;

use App\Enums\OtpCheck;
use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-time codes for signing in (Flow 1) and for resetting a forgotten password.
 *
 * - Stored only as a hash; the digits exist in the email (and, in local debug mode, the console).
 * - Short-lived (OtpPurpose::lifetimeSeconds) and single-use.
 * - Scoped by purpose: a code is only ever checked against its own purpose.
 * - Invalidated after MAX_ATTEMPTS wrong guesses, so a 6-digit code can't be brute-forced
 *   (the routes are rate-limited on top of this).
 */
class OtpService
{
    public const MAX_ATTEMPTS = 5;

    /**
     * Cancels the user's earlier unused codes for this purpose and returns a fresh one.
     */
    public function issue(User $user, OtpPurpose $purpose): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $purpose, $code) {
            $this->invalidate($user, $purpose);

            OtpCode::create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'code' => Hash::make($code),
                'expires_at' => now()->addSeconds($purpose->lifetimeSeconds()),
            ]);
        });

        return $code;
    }

    /**
     * Checks a code and uses it up on success. The row is locked while checking, so two guesses
     * sent at the same moment can't both slip under the attempts limit.
     */
    public function verify(User $user, OtpPurpose $purpose, string $code): OtpCheck
    {
        return DB::transaction(function () use ($user, $purpose, $code) {
            $otp = OtpCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return OtpCheck::NoActiveCode;
            }

            if (Hash::check($code, $otp->code)) {
                $otp->update(['used_at' => now()]);

                return OtpCheck::Valid;
            }

            $attempts = $otp->attempts + 1;
            $exhausted = $attempts >= self::MAX_ATTEMPTS;
            $otp->update(['attempts' => $attempts, 'used_at' => $exhausted ? now() : null]);

            return $exhausted ? OtpCheck::TooManyAttempts : OtpCheck::Wrong;
        });
    }

    /**
     * Cancels the user's unused codes — for one purpose, or all of them (e.g. after a password reset).
     */
    public function invalidate(User $user, ?OtpPurpose $purpose = null): void
    {
        OtpCode::query()
            ->where('user_id', $user->id)
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    /**
     * Costs about as much as issuing or checking a code. Used on the "no such account" paths of
     * the forgot-password flow so response times don't reveal which emails are registered.
     */
    public function spendEquivalentTime(): void
    {
        Hash::make(Str::random(16));
    }
}

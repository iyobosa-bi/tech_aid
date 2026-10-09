<?php

namespace App\Enums;

/**
 * The outcome of checking a one-time code (OtpService::verify). Callers show the user the same
 * message for every failure; the reason is for the activity log only.
 */
enum OtpCheck: string
{
    case Valid = 'valid';
    case Wrong = 'wrong_code';
    case NoActiveCode = 'no_active_code';      // expired, already used, or never issued
    case TooManyAttempts = 'too_many_attempts'; // this wrong guess used up the code's attempts; it's now invalid

    public function passed(): bool
    {
        return $this === self::Valid;
    }
}

<?php

namespace App\Enums;

/**
 * otp_codes.purpose. Codes are only ever checked against their own purpose, so a password-reset
 * code can't be used to sign in and a login code can't reset a password.
 */
enum OtpPurpose: string
{
    case Login = 'login';
    case PasswordReset = 'password_reset';

    // How long a code stays valid. The login modal and the reset page count this down.
    public function lifetimeSeconds(): int
    {
        return match ($this) {
            self::Login => 60,
            self::PasswordReset => 180,
        };
    }
}

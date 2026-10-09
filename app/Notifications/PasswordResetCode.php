<?php

namespace App\Notifications;

use App\Enums\OtpPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The 6-digit code for resetting a forgotten password. Only works on the reset page — never as a
 * login code (otp_codes.purpose).
 */
class PasswordResetCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $code,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = intdiv(OtpPurpose::PasswordReset->lifetimeSeconds(), 60);

        return (new MailMessage)
            ->subject('Your Tech Aid password reset code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this code to reset your Tech Aid password:')
            ->line("**{$this->code}**")
            ->line("It expires in {$minutes} minutes and can only be used once.")
            ->line('If you didn\'t ask to reset your password, you can ignore this email — your password stays the same. Never share this code with anyone, including IT staff.');
    }
}

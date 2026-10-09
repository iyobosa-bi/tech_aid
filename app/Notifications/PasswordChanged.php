<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security notice after a password change — through "Forgot password?" ($viaReset) or in Settings.
 * $changedAt is passed in because this is queued and may be sent a little later.
 */
class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CarbonInterface $changedAt,
        public readonly bool $viaReset = false,
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
        return (new MailMessage)
            ->subject('Your Tech Aid password was changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The password for your Tech Aid account was '.($this->viaReset ? 'reset' : 'changed')
                .' on '.$this->changedAt->format('M j, Y \a\t H:i').'.')
            ->line('You\'ve been signed out on your other devices. Next time you sign in, we\'ll still email you a login code.')
            ->line('**If this wasn\'t you**, contact the IT service desk straight away.');
    }
}

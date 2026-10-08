<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the support person a ticket was taken from when Head of Service Management
 * reassigns it (Flow 6), so they know to stop working on it.
 */
class TicketReassignedAway extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $newAssignee,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function databaseType(object $notifiable): string
    {
        return NotificationType::Reassigned->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} was reassigned")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("**{$this->ticket->title}** ({$this->ticket->ticket_number}) is now with {$this->newAssignee->name}.")
            ->line('You no longer need to work on it.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'message' => "{$this->ticket->ticket_number} was reassigned to {$this->newAssignee->name}.",
        ];
    }
}

<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to Head of Service Management when a line manager approves a ticket (Flow 3).
 */
class TicketAwaitingAssignment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function databaseType(object $notifiable): string
    {
        return NotificationType::AssignmentRequested->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is ready for assignment")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->ticket->lineManager->name} approved a ticket raised by {$this->ticket->requester->name}:")
            ->line("**{$this->ticket->title}**")
            ->line('Priority: '.ucfirst($this->ticket->priority))
            ->action('Assign ticket', route('tickets.show', $this->ticket));
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
            'message' => "{$this->ticket->lineManager->name} approved {$this->ticket->ticket_number}; it's ready for assignment.",
        ];
    }
}

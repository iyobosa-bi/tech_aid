<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when their line manager declines a ticket. The ticket is
 * returned, not closed — they can edit and resubmit it (Flows 3–4).
 */
class TicketReturned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $comment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} was returned to you")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->ticket->lineManager->name} returned your ticket **{$this->ticket->title}** with this comment:")
            ->line('“'.$this->comment.'”')
            ->line('You can edit the ticket and resubmit it for approval.')
            ->action('View ticket', route('tickets.show', $this->ticket));
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
            'message' => "{$this->ticket->lineManager->name} returned {$this->ticket->ticket_number} to you.",
        ];
    }
}

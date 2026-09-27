<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAwaitingApproval extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is awaiting your approval")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->ticket->requester->name} raised a new ticket that needs your approval:")
            ->line("**{$this->ticket->title}**")
            ->line('Priority: '.ucfirst($this->ticket->priority))
            ->line('Please sign in to Tech Aid to approve or reject it.');
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
            'message' => "{$this->ticket->requester->name} raised {$this->ticket->ticket_number} for your approval.",
        ];
    }
}

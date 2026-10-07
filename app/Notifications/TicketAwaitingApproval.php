<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    // $resubmitted: the requester edited a ticket that was returned to them (Flow 4).
    public function __construct(
        public readonly Ticket $ticket,
        public readonly bool $resubmitted = false,
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
        return NotificationType::ApprovalRequested->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $intro = $this->resubmitted
            ? "{$this->ticket->requester->name} updated a ticket you returned and resubmitted it for your approval:"
            : "{$this->ticket->requester->name} raised a new ticket that needs your approval:";

        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is awaiting your approval")
            ->greeting('Hello '.$notifiable->name.',')
            ->line($intro)
            ->line("**{$this->ticket->title}**")
            ->line('Priority: '.ucfirst($this->ticket->priority))
            ->action('Review ticket', route('tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $verb = $this->resubmitted ? 'resubmitted' : 'raised';

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'message' => "{$this->ticket->requester->name} {$verb} {$this->ticket->ticket_number} for your approval.",
        ];
    }
}

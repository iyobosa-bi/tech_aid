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
 * Sent to the Application Support person a ticket was just given to (Flows 5–6) — by Head of
 * Service Management ($assignedBy), or automatically when $assignedBy is null.
 */
class TicketAssignedToYou extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?User $assignedBy,
        public readonly bool $reassigned = false,
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
        return NotificationType::Assigned->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is assigned to you")
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->sentence().':')
            ->line("**{$this->ticket->title}**")
            ->line('Priority: '.ucfirst($this->ticket->priority).' · Raised by '.$this->ticket->requester->name)
            ->action('Open ticket', route('tickets.show', $this->ticket));
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
            'message' => $this->sentence().'.',
        ];
    }

    private function sentence(): string
    {
        $number = $this->ticket->ticket_number;

        return match (true) {
            $this->assignedBy === null => "{$number} was auto-assigned to you",
            $this->reassigned => "{$this->assignedBy->name} reassigned {$number} to you",
            default => "{$this->assignedBy->name} assigned {$number} to you",
        };
    }
}

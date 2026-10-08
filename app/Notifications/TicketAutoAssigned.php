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
 * For Head of Service Management's information: with auto-assign ON, an approved ticket went
 * straight to the least busy support person (Flow 5). They can still reassign it.
 */
class TicketAutoAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $assignee,
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
            ->subject("Ticket {$this->ticket->ticket_number} was auto-assigned to {$this->assignee->name}")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->ticket->lineManager->name} approved **{$this->ticket->title}**, and auto-assign gave it to {$this->assignee->name}, who had the fewest open tickets.")
            ->line('You can reassign it from the ticket page if needed.')
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
            'message' => "{$this->ticket->ticket_number} was auto-assigned to {$this->assignee->name} (least busy).",
        ];
    }
}

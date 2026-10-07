<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to everyone else on a ticket when someone adds a message to its conversation.
 */
class TicketMessagePosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $author,
        public readonly string $body,
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
        return NotificationType::Message->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New message on ticket {$this->ticket->ticket_number}")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("{$this->author->name} wrote on **{$this->ticket->title}**:")
            ->line('“'.Str::limit($this->body, 500).'”')
            ->action('Reply', route('tickets.show', $this->ticket).'#conversation');
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
            'message' => "{$this->author->name} sent a message on {$this->ticket->ticket_number}.",
        ];
    }
}

<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the requester their ticket moved to a new stage (Flow 10): raised, approved,
 * resubmitted — later assigned, resolved, reopened. A decline sends TicketReturned instead.
 *
 * $status is passed in rather than read from the ticket: this is queued, and by the time
 * the job runs the ticket may already have moved on again.
 */
class TicketStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $status,
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
        return NotificationType::StatusUpdate->value;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is now {$this->status->label()}")
            ->greeting('Hello '.$notifiable->name.',')
            ->line(Str::ucfirst($this->sentence()).'.')
            ->line("**{$this->ticket->title}**")
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
            'status' => $this->status->value,
            'message' => "{$this->ticket->requester->name}, {$this->sentence()}",
        ];
    }

    // "your ticket TA-000189 is APPROVED and PENDING ASSIGNMENT" — the stage in capitals, as in the design.
    private function sentence(): string
    {
        $stage = match ($this->status) {
            TicketStatus::PendingLineManagerApproval => "PENDING LINE MANAGER ({$this->ticket->lineManager->name})",
            TicketStatus::PendingAssignment => 'APPROVED and PENDING ASSIGNMENT',
            default => Str::upper($this->status->label()),
        };

        return "your ticket {$this->ticket->ticket_number} is {$stage}";
    }
}

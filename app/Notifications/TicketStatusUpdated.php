<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the requester their ticket moved to a new stage (Flow 10): raised, approved,
 * resubmitted, assigned, in progress, resolved — later reopened. A decline sends TicketReturned instead.
 *
 * $status (and $assignee) are passed in rather than read from the ticket: this is queued, and
 * by the time the job runs the ticket may already have moved on again.
 *
 * $approvedNow: auto-assign gave the ticket to support in the same moment it was approved, so
 * the requester gets one "APPROVED and ASSIGNED" update instead of two back to back.
 * $notes: the resolution notes, included in the email when the ticket is resolved.
 * $attachmentCount: files added with the resolution — the email points to them on the ticket
 * (bank documents stay in the app; they are never attached to the email itself).
 */
class TicketStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $status,
        public readonly ?User $assignee = null,
        public readonly bool $approvedNow = false,
        public readonly ?string $notes = null,
        public readonly int $attachmentCount = 0,
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
        $mail = (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is now {$this->status->label()}")
            ->greeting('Hello '.$notifiable->name.',')
            ->line(Str::ucfirst($this->sentence()).'.')
            ->line("**{$this->ticket->title}**");

        if ($this->notes) {
            $mail->line('Resolution notes:')->line('“'.$this->notes.'”');
        }

        if ($this->attachmentCount > 0) {
            $mail->line($this->attachmentCount.' '.Str::plural('file', $this->attachmentCount).' from the resolution '
                .($this->attachmentCount === 1 ? 'is' : 'are').' attached to the ticket.');
        }

        return $mail->action('View ticket', route('tickets.show', $this->ticket));
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
        $stage = match (true) {
            $this->status === TicketStatus::PendingLineManagerApproval => "PENDING LINE MANAGER ({$this->ticket->lineManager->name})",
            $this->status === TicketStatus::PendingAssignment => 'APPROVED and PENDING ASSIGNMENT',
            $this->status === TicketStatus::Assigned && $this->assignee !== null => ($this->approvedNow ? 'APPROVED and ' : '')."ASSIGNED to {$this->assignee->name}",
            $this->status === TicketStatus::InProgress && $this->assignee !== null => "IN PROGRESS with {$this->assignee->name}",
            default => Str::upper($this->status->label()),
        };

        return "your ticket {$this->ticket->ticket_number} is {$stage}";
    }
}

<?php

namespace App\Models;

use App\Enums\TicketAction;
use App\Services\TicketTransitionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStatusHistory extends Model
{
    protected $table = 'ticket_status_history';

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'actor_role', // role of the person who did this, not who has the ticket next (see to_status)
        'action',
        'from_status',
        'to_status',
        'comment',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // withTrashed: a deactivated user's past messages and decisions keep their name (audit trail).
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    // Done by the system on its own (auto-assign): no person, actor_role 'System'.
    public function isSystem(): bool
    {
        return $this->actor_id === null && $this->actor_role === TicketTransitionService::SYSTEM_ROLE;
    }

    // A plain conversation message, as opposed to a status change.
    public function isComment(): bool
    {
        return $this->action === TicketAction::Commented->value;
    }

    // Carries text worth showing in the conversation: a message, or e.g. a decline reason.
    public function hasMessage(): bool
    {
        return filled($this->comment);
    }
}

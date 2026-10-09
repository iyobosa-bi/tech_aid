<?php

namespace App\Models;

use App\Enums\PermissionName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'line_manager_id',
        'assigned_to_id',
        'assigned_by_id',
        'assigned_at',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'resolution_notes',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Tickets the user may see — must stay in step with TicketPolicy::view().
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        // Head of Service Management, and Admins (read-only, Admin → All tickets), see every ticket.
        if ($user->checkPermissionTo(PermissionName::AssignTickets) || $user->checkPermissionTo(PermissionName::ManageUsers)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('requester_id', $user->id)
            ->orWhere('line_manager_id', $user->id)
            ->orWhere('assigned_to_id', $user->id));
    }

    // Users are soft-deleted, never removed, so historical tickets keep resolving
    // their people (audit trail) — hence withTrashed() on every user relation.
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id')->withTrashed();
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'line_manager_id')->withTrashed();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id')->withTrashed();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id')->withTrashed();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class);
    }
}

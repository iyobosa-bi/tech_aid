<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PermissionName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'line_manager_id', 'department', 'on_leave'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'on_leave' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Deactivated by an Admin (reversible): can't sign in, signed out on their next request,
     * never offered for assignment. Deleting is separate — a soft delete (SoftDeletes).
     * deactivated_at is not mass-assignable; UserRepository::setActive() sets it.
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    /**
     * Short handle for logs, e.g. "adaeze.okafor" for adaeze.okafor@optimusbank.com.
     * There's no username column; staff emails share one domain, so the part before
     * the @ is already unique.
     */
    public function username(): string
    {
        return Str::before($this->email, '@');
    }

    public function handlesTickets(): bool
    {
        return collect(PermissionName::handling())->contains(fn (PermissionName $permission) => $this->checkPermissionTo($permission));
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'line_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'line_manager_id');
    }

    public function ticketsRequested(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function ticketsAsLineManager(): HasMany
    {
        return $this->hasMany(Ticket::class, 'line_manager_id');
    }

    public function ticketsAssignedToMe(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to_id');
    }

    public function ticketsAssignedByMe(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_by_id');
    }
}

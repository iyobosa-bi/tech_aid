<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * In-app notifications. Every query starts from $user->notifications(), so nobody can
 * ever read or change someone else's (a stranger's id is simply "not found").
 */
class NotificationRepository
{
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function latest(User $user, int $limit): Collection
    {
        return $user->notifications()->limit($limit)->get();
    }

    /**
     * Newest first (the relation's own order).
     *
     * @param  array{status: ?string, type: ?string, from: ?string, to: ?string}  $filters
     */
    public function paginateFor(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $user->notifications()
            ->when($filters['status'] === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filters['status'] === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($filters['type'], fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['from'], fn ($query, string $from) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'], fn ($query, string $to) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForUser(User $user, string $id): DatabaseNotification
    {
        return $user->notifications()->findOrFail($id);
    }

    public function markRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}

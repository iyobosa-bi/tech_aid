<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use App\Repositories\NotificationRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The bell dropdown and the "All Notifications" page (Flow 10). Dates and times are
 * formatted here, in app time (Africa/Lagos), so the browser only has to display them.
 */
class NotificationService
{
    // How many of the newest notifications the bell dropdown shows.
    public const DROPDOWN_LIMIT = 10;

    public function __construct(private readonly NotificationRepository $notifications) {}

    /**
     * What the bell polls for: the unread count and the newest notifications, grouped by day.
     *
     * @return array{unread: int, groups: list<array{day: string, items: list<array<string, mixed>>}>}
     */
    public function feedFor(User $user): array
    {
        $groups = $this->notifications->latest($user, self::DROPDOWN_LIMIT)
            ->groupBy(fn (DatabaseNotification $notification) => $this->dayLabel($notification->created_at))
            ->map(fn ($items, string $day) => ['day' => $day, 'items' => $items->map($this->present(...))->values()->all()])
            ->values()
            ->all();

        return ['unread' => $this->notifications->unreadCount($user), 'groups' => $groups];
    }

    /**
     * @param  array{status: ?string, type: ?string, from: ?string, to: ?string, per_page: int}  $filters
     */
    public function pageFor(User $user, array $filters): LengthAwarePaginator
    {
        return $this->notifications->paginateFor($user, $filters, $filters['per_page'])
            ->through($this->present(...));
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCount($user);
    }

    /**
     * Marks the notification read and returns where it points (its ticket).
     */
    public function open(User $user, string $id): string
    {
        $notification = $this->notifications->findForUser($user, $id);
        $this->notifications->markRead($notification);

        $ticketId = $notification->data['ticket_id'] ?? null;

        if (! $ticketId) {
            return route('notifications.index');
        }

        $url = route('tickets.show', $ticketId);

        return $notification->type === NotificationType::Message->value ? "{$url}#conversation" : $url;
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllRead($user);
    }

    /**
     * @return array{id: string, tag: string, message: string, time: string, date: string, full_date: string, datetime: string, read: bool, url: string}
     */
    private function present(DatabaseNotification $notification): array
    {
        $at = $notification->created_at;

        return [
            'id' => $notification->id,
            'tag' => NotificationType::tryFrom($notification->type)?->label() ?? 'Update',
            'message' => $notification->data['message'] ?? '',
            'time' => $at->format('h:i A'),
            'date' => $at->format('n/j/Y'),
            'full_date' => $at->format('M j, Y · h:i A'),
            'datetime' => $at->toIso8601String(),
            'read' => $notification->read_at !== null,
            'url' => route('notifications.open', $notification->id),
        ];
    }

    private function dayLabel(CarbonInterface $at): string
    {
        return match (true) {
            $at->isToday() => 'Today',
            $at->isYesterday() => 'Yesterday',
            $at->isCurrentYear() => $at->format('M j'),
            default => $at->format('M j, Y'),
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Http\Requests\ListNotificationsRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Flow 10: the bell's polled feed and the "All Notifications" page. Every action works on
 * the signed-in user's own notifications only (see NotificationRepository).
 */
class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request, NotificationService $notifications): View
    {
        $filters = $request->filters();

        return view('notifications.index', [
            'notifications' => $notifications->pageFor($request->user(), $filters),
            'filters' => $filters,
            'activeFilters' => $request->activeFilterCount(),
            'unread' => $notifications->unreadCount($request->user()),
            'types' => NotificationType::cases(),
            'perPageOptions' => ListNotificationsRequest::PER_PAGE_OPTIONS,
        ]);
    }

    // Polled by the bell every 15 seconds (public/js/notifications.js).
    public function feed(Request $request, NotificationService $notifications): JsonResponse
    {
        return response()->json($notifications->feedFor($request->user()));
    }

    // A GET, so the link also works with middle-click / "open in new tab"; marking read twice is harmless.
    public function open(Request $request, string $notification, NotificationService $notifications): RedirectResponse
    {
        return redirect()->to($notifications->open($request->user(), $notification));
    }

    public function markAllRead(Request $request, NotificationService $notifications): RedirectResponse
    {
        $count = $notifications->markAllRead($request->user());

        return back()->with('success', $count
            ? 'All notifications are marked as read.'
            : 'You have no unread notifications.');
    }
}

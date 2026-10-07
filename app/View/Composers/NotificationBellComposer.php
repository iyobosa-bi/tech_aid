<?php

namespace App\View\Composers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hands the app layout the bell's first feed, so the unread badge is right on page load
 * instead of appearing after the first 15-second poll. Keeps the queries out of Blade.
 */
class NotificationBellComposer
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly Request $request,
    ) {}

    public function compose(View $view): void
    {
        $user = $this->request->user();

        $view->with('notificationFeed', $user ? $this->notifications->feedFor($user) : null);
    }
}

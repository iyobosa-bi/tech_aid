<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DashboardService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Every signed-in user has a dashboard; which figures and tickets they see is decided
    // by DashboardService, whose queries are scoped to that user.
    public function index(Request $request, DashboardService $dashboard, SettingService $settings): View
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => $dashboard->cardsFor($user),
            'recentTickets' => $dashboard->recentTicketsFor($user),
            // Same rule as the Tickets page: requesters only see their own tickets, so no Name column.
            'showRequester' => $user->handlesTickets(),
            // Admin only: the auto-assign state, with a link to System settings.
            'autoAssign' => $user->can('manage', Setting::class) ? $settings->autoAssignEnabled() : null,
        ]);
    }
}

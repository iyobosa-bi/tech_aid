<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Every signed-in user has a dashboard; which figures and tickets they see is decided
    // by DashboardService, whose queries are scoped to that user.
    public function index(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => $dashboard->cardsFor($user),
            'recentTickets' => $dashboard->recentTicketsFor($user),
            // Same rule as the Tickets page: requesters only see their own tickets, so no Name column.
            'showRequester' => $user->handlesTickets(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Every signed-in user has a dashboard; which figures they see is decided by the
    // role branch in DashboardService, whose queries are scoped to that user.
    public function index(Request $request, DashboardService $dashboard): View
    {
        return view('dashboard', [
            'stats' => $dashboard->cardsFor($request->user()),
        ]);
    }
}

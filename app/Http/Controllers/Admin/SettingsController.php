<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use App\Services\SupportAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → System settings: the auto-assign switch (Flow 5) and who in Application Support is
 * available for it. Only users with "manage settings" (Admin) — see SettingPolicy.
 */
class SettingsController extends Controller
{
    public function index(SettingService $settings, SupportAssignmentService $assignments): View
    {
        $this->authorize('manage', Setting::class);

        $staff = $assignments->staff();
        $bucket = $assignments->bucket($staff);

        return view('admin.settings', [
            'autoAssign' => $settings->autoAssignEnabled(),
            'staff' => $staff,
            'availableCount' => $bucket->count(),
            'nextUp' => $assignments->leastBusy($bucket),
        ]);
    }

    public function updateAutoAssign(Request $request, SettingService $settings): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $settings->setAutoAssign((bool) $enabled, $request->user());

        return back()->with('success', $enabled
            ? 'Auto-assign is on.'
            : 'Auto-assign is off. Head of Service Management assigns approved tickets by hand.');
    }

    public function updateAvailability(Request $request, int $supportUser, SupportAssignmentService $assignments): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $onLeave = (bool) $request->validate(['on_leave' => ['required', 'boolean']])['on_leave'];
        $person = $assignments->setOnLeave($supportUser, $onLeave, $request->user());

        return back()->with('success', $onLeave
            ? "{$person->name} is on leave and won't be auto-assigned tickets."
            : "{$person->name} is available for auto-assign again.");
    }
}

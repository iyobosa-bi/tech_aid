<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListUsersRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Users: every account, with Activate / Deactivate and Delete. UserPolicy decides who
 * may (Admin only, never on their own account); UserAdministrationService does the work.
 */
class UserController extends Controller
{
    public function index(ListUsersRequest $request, UserRepository $users): View
    {
        return view('admin.users', [
            'users' => $users->paginateForAdmin($request->filters()),
            'filters' => $request->filters(),
            'roles' => RoleName::cases(),
            'counts' => $users->accountCounts(),
        ]);
    }

    public function updateStatus(Request $request, User $user, UserAdministrationService $admin): RedirectResponse
    {
        $active = (bool) $request->validate(['active' => ['required', 'boolean']])['active'];
        $this->authorize($active ? 'activate' : 'deactivate', $user);

        $admin->setActive($user, $active, $request->user());

        return back()->with('success', $active
            ? "{$user->name} can sign in again."
            : "{$user->name}'s account is deactivated. They've been signed out and can't sign in until you activate it again.");
    }

    public function destroy(Request $request, User $user, UserAdministrationService $admin): RedirectResponse
    {
        $this->authorize('delete', $user);

        $admin->delete($user, $request->user());

        return back()->with('success', "{$user->name}'s account was deleted. Their tickets and history are kept.");
    }
}

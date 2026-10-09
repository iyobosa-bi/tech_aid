<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password (Settings). The only thing staff can change about their own
     * account. Other sessions are signed out and the user gets a security email (PasswordService).
     */
    public function update(Request $request, PasswordService $passwords): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $passwords->change($request->user(), $validated['password'], (string) $request->ip());

        return back()->with('status', 'password-updated');
    }
}

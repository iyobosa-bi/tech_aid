<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings: staff see their name and email (read-only — an Admin manages them in Admin → Users)
 * and can change their password (Auth\PasswordController). Nobody can delete their own account.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }
}

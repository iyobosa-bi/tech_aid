<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Forgot password, in three steps: email → 6-digit code → new password (see PasswordService for
 * the security rules). Progress lives in the session: the email typed in step 1, then — only once
 * a code matched — the verified user's id, valid for PasswordService::VERIFIED_FOR_MINUTES.
 *
 * Steps 1 and 2 never say whether the email belongs to an account: the messages, redirects and
 * countdown are the same either way. Resetting doesn't sign anyone in, so the next sign-in still
 * needs a login code.
 */
class PasswordResetController extends Controller
{
    private const EMAIL = 'password_reset.email';

    private const REQUESTED_AT = 'password_reset.requested_at';

    private const VERIFIED_USER = 'password_reset.user_id';

    private const VERIFIED_AT = 'password_reset.verified_at';

    public const SENT_MESSAGE = 'If an account exists for that email, we\'ve sent it a 6-digit code.';

    public const INVALID_CODE_MESSAGE = 'That code isn\'t valid or has expired. Check the latest email, or request a new code.';

    public const EXPIRED_MESSAGE = 'Your reset session has expired. Please request a new code.';

    public function __construct(private readonly PasswordService $passwords) {}

    // Step 1: which email?
    public function create(Request $request): View
    {
        return view('auth.forgot-password', ['email' => $request->session()->get(self::EMAIL, '')]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $email = Str::lower(trim($request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            ['email.required' => 'Enter the email you sign in with.', 'email.email' => 'Enter a valid email address.'],
        )['email']));

        $this->forgetProgress($request);
        $code = $this->passwords->sendResetCode($email, (string) $request->ip());
        $request->session()->put([self::EMAIL => $email, self::REQUESTED_AT => now()->timestamp]);

        return redirect()->route('password.code')->with('debug_code', $this->debugCode($code));
    }

    // Step 2: the code from the email.
    public function showCode(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::EMAIL)) {
            return redirect()->route('password.request');
        }

        // Counted from when the code was asked for, not from the code itself — so it's the same
        // whether or not an account (and so a code) exists.
        $requestedAt = (int) $request->session()->get(self::REQUESTED_AT);

        return view('auth.reset-code', [
            'email' => $request->session()->get(self::EMAIL),
            'secondsLeft' => max(0, $requestedAt + OtpPurpose::PasswordReset->lifetimeSeconds() - now()->timestamp),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL);

        if (! $email) {
            return redirect()->route('password.request')->with('error', self::EXPIRED_MESSAGE);
        }

        $code = $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Enter the 6-digit code from the email.', 'code.digits' => 'Enter the 6-digit code from the email.'],
        )['code'];

        $user = $this->passwords->verifyResetCode($email, $code, (string) $request->ip());

        if (! $user) {
            return back()->withErrors(['code' => self::INVALID_CODE_MESSAGE]);
        }

        // A new session id once verified, so a session id planted beforehand is worthless.
        $request->session()->regenerate();
        $request->session()->forget([self::EMAIL, self::REQUESTED_AT]);
        $request->session()->put([self::VERIFIED_USER => $user->id, self::VERIFIED_AT => now()->timestamp]);

        return redirect()->route('password.reset');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL);

        if (! $email) {
            return redirect()->route('password.request')->with('error', self::EXPIRED_MESSAGE);
        }

        $code = $this->passwords->sendResetCode($email, (string) $request->ip());
        $request->session()->put(self::REQUESTED_AT, now()->timestamp);

        return redirect()->route('password.code')
            ->with('status', 'If an account exists for that email, we\'ve sent it a new code.')
            ->with('debug_code', $this->debugCode($code));
    }

    // Step 3: the new password.
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedUser($request)) {
            return redirect()->route('password.request')->with('error', self::EXPIRED_MESSAGE);
        }

        return view('auth.reset-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->verifiedUser($request);

        if (! $user) {
            $this->forgetProgress($request);

            return redirect()->route('password.request')->with('error', self::EXPIRED_MESSAGE);
        }

        $password = $request->validate([
            'password' => [
                'required', 'confirmed', Password::defaults(),
                function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    if (is_string($value) && Hash::check($value, $user->password)) {
                        $fail('Choose a password you haven\'t been using.');
                    }
                },
            ],
        ])['password'];

        $this->passwords->reset($user, $password, (string) $request->ip());

        $this->forgetProgress($request);
        $request->session()->regenerate();

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password — we\'ll email you a login code as usual.');
    }

    // The account that proved it owns the email, while that proof is still fresh.
    private function verifiedUser(Request $request): ?User
    {
        $id = $request->session()->get(self::VERIFIED_USER);
        $at = (int) $request->session()->get(self::VERIFIED_AT);

        if (! $id || now()->timestamp - $at > PasswordService::VERIFIED_FOR_MINUTES * 60) {
            return null;
        }

        return User::active()->find($id);
    }

    private function forgetProgress(Request $request): void
    {
        $request->session()->forget([self::EMAIL, self::REQUESTED_AT, self::VERIFIED_USER, self::VERIFIED_AT]);
    }

    // Local-dev convenience, like the login OTP: the reset page prints the code in the browser console.
    private function debugCode(?string $code): ?string
    {
        return $code !== null && app()->isLocal() && config('app.debug') ? $code : null;
    }
}

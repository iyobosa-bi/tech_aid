<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpCheck;
use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Notifications\LoginOtpCode;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly OtpService $otps) {}

    /**
     * Display the login view.
     *
     * The OTP modal only auto-opens for the single response immediately
     * following a successful password check (a one-shot session flash) —
     * never merely because a pending challenge still exists server-side.
     * That keeps browser back/forward navigation from re-presenting a modal
     * the user didn't just earn with a fresh, valid password submission.
     * The route's `cache.headers:no_store` middleware (see routes/auth.php)
     * also stops the browser from serving this page out of its back/forward
     * cache, which would otherwise show a stale, already-open modal — or a
     * stale pre-login form — without a server round-trip at all.
     */
    public function create(Request $request): View
    {
        $otpJustIssued = (bool) $request->session()->get('otp_pending', false);

        if (!$otpJustIssued) {
            $this->clearPendingOtp($request);
        }

        return view('auth.login', ['otpPending' => $otpJustIssued]);
    }

    /**
     * Validate credentials and, if they check out, email a one-time code
     * instead of starting the session immediately (see docs/03-user-flows.md,
     * Flow 1). The login page reloads and auto-opens the OTP modal.
     *
     * Any OTP challenge left over from an earlier attempt on this session is
     * torn down up front, before the new credentials are even checked. Without
     * this, a wrong password on a second attempt would throw before reaching
     * the code below, leaving the *previous* attempt's still-valid pending
     * state (and its unexpired code) in place — which is what let a wrong
     * password still open the modal and let any leftover code log in.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $this->clearPendingOtp($request);

        $user = $request->authenticate();

        $code = $this->issueOtp($user);

        $request->session()->put('login.otp.user_id', $user->id);

        return $this->otpResponse([
            'success' => true,
            'otp_pending' => true,
        ], $code);
    }

    /**
     * Verify the emailed code and complete the login.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('login.otp.user_id');
        // Deleted or deactivated since the password step: treat it like an expired login.
        $user = $userId ? User::active()->find($userId) : null;

        if (! $user) {
            $this->logFailedOtp($request, 'session_expired');

            return response()->json([
                'message' => 'Your session has expired. Please log in again.',
            ], 419);
        }

        $check = $this->otps->verify($user, OtpPurpose::Login, (string) $request->string('code'));

        if (! $check->passed()) {
            $this->logFailedOtp($request, $check->value, $user);

            // After too many wrong tries the code is dead: the modal unlocks "Resend OTP" straight away.
            return $check === OtpCheck::TooManyAttempts
                ? response()->json(['message' => 'Too many wrong codes. Please request a new one.', 'code_expired' => true], 422)
                : response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        Auth::login($user);
        $request->session()->forget('login.otp.user_id');
        $request->session()->regenerate();

        Log::channel('activity')->info('OTP verified', [
            'user_id' => $user->id,
            'username' => $user->username(),
        ]);

        return response()->json([
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Resend a fresh code to the user mid-login. Rate-limited at the route
     * level (max 3 per 10 minutes, per docs/03-user-flows.md Flow 1).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $userId = $request->session()->get('login.otp.user_id');
        $user = $userId ? User::active()->find($userId) : null;

        if (! $user) {
            return response()->json([
                'message' => 'Your session has expired. Please log in again.',
            ], 419);
        }

        $code = $this->issueOtp($user);

        return $this->otpResponse([
            'message' => 'A new code has been sent to your email.',
        ], $code);
    }

    /**
     * Abandon a pending OTP login (e.g. the user wants to try a different
     * account) without waiting for the code to expire.
     */
    public function cancelOtp(Request $request): JsonResponse
    {
        $this->clearPendingOtp($request);

        return response()->json(['ok' => true]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Cancel any outstanding login codes, generate a new one, and email it.
     * The database stores only a hash (docs/06-data-model.md); the plain code
     * lives in the queued notification and, in local dev only, otpResponse().
     */
    private function issueOtp(User $user): string
    {
        $code = $this->otps->issue($user, OtpPurpose::Login);

        $user->notify(new LoginOtpCode($code));

        // Who the code is for — never the code itself.
        Log::channel('activity')->info('OTP generated', [
            'user_id' => $user->id,
            'username' => $user->username(),
        ]);

        return $code;
    }

    /**
     * Activity log entry for a rejected code. $user is null when the pending login has
     * already expired. The submitted digits are never logged.
     */
    private function logFailedOtp(Request $request, string $reason, ?User $user = null): void
    {
        Log::channel('activity')->warning('OTP verification failed', array_filter([
            'user_id' => $user?->id,
            'username' => $user?->username(),
            'reason' => $reason,
            'ip' => $request->ip(),
        ]));
    }

    /**
     * Every response that issues a code tells the modal how many seconds it
     * stays valid (`expires_in`), so its countdown matches the server.
     *
     * Local-dev convenience: also expose the plain code so the login page can
     * console.log it instead of digging through the mail log. Requires BOTH
     * APP_ENV=local and APP_DEBUG=true, so it can never reach production.
     *
     * @param  array<string, mixed>  $payload
     */
    private function otpResponse(array $payload, string $code): JsonResponse
    {
        $payload['expires_in'] = OtpPurpose::Login->lifetimeSeconds();

        if (app()->isLocal() && config('app.debug')) {
            $payload['debug_code'] = $code;
        }

        return response()->json($payload);
    }

    /**
     * Tear down whatever OTP challenge this session was mid-way through, if
     * any: forget the session key and invalidate its still-unused code so it
     * can never be replayed later. Called before a fresh login attempt, when
     * a stale challenge is detected on a plain page revisit, and on explicit
     * cancel — every path by which a pending challenge should die.
     */
    private function clearPendingOtp(Request $request): void
    {
        $previousUserId = $request->session()->pull('login.otp.user_id');
        $previous = $previousUserId ? User::withTrashed()->find($previousUserId) : null;

        if ($previous) {
            $this->otps->invalidate($previous, OtpPurpose::Login);
        }
    }
}

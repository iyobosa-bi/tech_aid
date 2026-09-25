<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\LoginOtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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
    public function store(LoginRequest $request): RedirectResponse
    {
        
        $this->clearPendingOtp($request);

        $user = $request->authenticate();
        
        $this->issueOtp($user);
        
        $request->session()->put('login.otp.user_id', $user->id);
        $request->session()->flash('otp_pending', true);

        return redirect()->route('login');
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

        if (! $userId) {
            return response()->json([
                'message' => 'Your session has expired. Please log in again.',
            ], 419);
        }

        $otp = OtpCode::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || ! Hash::check($request->string('code'), $otp->code)) {
            return response()->json([
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        $otp->update(['used_at' => now()]);

        $user = User::findOrFail($userId);

        Auth::login($user);
        $request->session()->forget('login.otp.user_id');
        $request->session()->regenerate();

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

        if (!$userId) {
            return response()->json([
                'message' => 'Your session has expired. Please log in again.',
            ], 419);
        }

        $this->issueOtp(User::findOrFail($userId));

        return response()->json([
            'message' => 'A new code has been sent.',
        ]);
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
     * Invalidate any outstanding codes, generate a new one, and email it.
     * The plain code only ever exists in the queued notification — the
     * database stores a hash, per docs/06-data-model.md.
     */
    private function issueOtp(User $user): void
    {
        OtpCode::where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
         
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(1),
        ]);

        $user->notify(new LoginOtpCode($code));
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

        if ($previousUserId) {
            OtpCode::where('user_id', $previousUserId)->whereNull('used_at')->update(['used_at' => now()]);
        }
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Validate the request's credentials, without starting a session.
     *
     * The OTP step (see AuthenticatedSessionController::verifyOtp) is what
     * actually calls Auth::login() once the emailed code is confirmed.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            // The reason is for operators only — the user still sees the same generic message.
            $this->logFailedLogin(User::where('email', $this->string('email'))->exists() ? 'wrong_password' : 'unknown_email');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = User::where('email', $this->string('email'))->firstOrFail();

        // Step 1 of 2: the password checked out. The session itself starts at "OTP verified".
        Log::channel('activity')->info('Login succeeded', [
            'user_id' => $user->id,
            'username' => $user->username(),
            'ip' => $this->ip(),
        ]);

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $this->logFailedLogin('too_many_attempts');

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Activity log entry for a rejected sign-in. Records what was typed in the email
     * field and where from — never the password.
     */
    private function logFailedLogin(string $reason): void
    {
        Log::channel('activity')->warning('Login failed', [
            'email_attempted' => (string) $this->string('email'),
            'ip' => $this->ip(),
            'reason' => $reason,
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

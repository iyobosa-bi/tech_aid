<?php

namespace App\Providers;

use App\View\Composers\NotificationBellComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The notification bell in the app layout's topbars (Flow 10).
        View::composer('layouts.app', NotificationBellComposer::class);

        // Every new password (forgot-password reset and Settings): at least 8 characters with
        // upper and lower case, a number and a symbol. The reset page shows these as a checklist.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

        // Forgot-password codes: 3 per 10 minutes for any one email address (so nobody can flood a
        // colleague's inbox), and 10 per 10 minutes from one IP (so one machine can't spray many
        // addresses). Keyed by what was typed, so it behaves the same whether the account exists.
        RateLimiter::for('password-reset-codes', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by('email:'.Str::lower(trim((string) ($request->input('email') ?? $request->session()->get('password_reset.email'))))),
            Limit::perMinutes(10, 10)->by('ip:'.$request->ip()),
        ]);
    }
}

<?php

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Each session remembers the password it signed in with; once the password changes
            // (forgot-password reset, or Settings), every other session is signed out.
            AuthenticateSession::class,
            // An Admin deactivated the account: signed out on the next request.
            EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $wantsJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($wantsJson);

        // Forgot-password pages are plain forms: show the limit on the page, not a bare 429 screen.
        // The limit is keyed by the typed email, so this reads the same whether the account exists.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request) || ! $request->routeIs('password.*')) {
                return null;
            }

            $minutes = max(1, (int) ceil(((int) ($e->getHeaders()['Retry-After'] ?? 60)) / 60));

            return redirect()->back()->with('error', "Too many requests. Please wait {$minutes} ".($minutes === 1 ? 'minute' : 'minutes').' and try again.');
        });

        // The handler converts AuthorizationException into AccessDeniedHttpException
        // (prepareException) BEFORE render callbacks run, so match that and check the
        // original cause. denyWithStatus()/denyAsNotFound() become other HttpExceptions
        // and plain abort(403) has no AuthorizationException cause — both keep Laravel's
        // default rendering, as do JSON requests (returning null falls through).
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($wantsJson) {
            if (! $e->getPrevious() instanceof AuthorizationException || $wantsJson($request)) {
                return null;
            }

            $fallback = route('dashboard');
            $target = url()->previous($fallback);

            // Redirecting "back" to the page that was just denied would loop forever.
            if ($target === $request->fullUrl()) {
                $target = $fallback;
            }

            if ($target === $request->fullUrl()) {
                return null;
            }

            return redirect()->to($target)->with('error', $e->getMessage());
        });
    })->create();

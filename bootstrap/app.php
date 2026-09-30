<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $wantsJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($wantsJson);

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

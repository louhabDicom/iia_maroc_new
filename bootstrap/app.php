<?php

use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Http\Middleware\LocalizeUrls;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxy trust is configured in AppServiceProvider::register(), not here:
        // this closure runs before the configuration is loaded, so config()
        // is not yet bound and the request would fail to resolve.

        // Appending rather than prepending: the session must exist before
        // SetLocale can read a stored preference from it, and authentication has
        // to run before EnsurePhoneIsVerified can see who is asking.
        $middleware->web(append: [
            SetLocale::class,
            LocalizeUrls::class,
        ]);

        // The CMI server-to-server callback is the one route that cannot carry
        // a CSRF token: it is a request from a third-party server, with no
        // session and no form behind it. It is authenticated by the gateway's
        // signature instead, which PaymentController verifies before acting on
        // anything. Excluding it narrowly — by path, not wholesale — keeps
        // CSRF protection on every other POST in the application.
        $middleware->validateCsrfTokens(except: [
            'payment/cmi/callback',
        ]);

        $middleware->alias([
            'verified.phone' => EnsurePhoneIsVerified::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

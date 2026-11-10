<?php

use App\Http\Middleware\EnsureNotDemo;
use App\Http\Middleware\EnsureRegistrationEnabled;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->append(SecurityHeaders::class);

        // Rejects requests for any host other than APP_URL's, so links built from the request,
        // such as password reset links, can't point at an attacker's domain. Off in local and tests.
        $middleware->trustHosts();

        // Mail apps' one-click unsubscribe posts without a CSRF token; the signed URL protects it.
        $middleware->validateCsrfTokens(except: ['unsubscribe/*']);

        $middleware->alias([
            'registration.enabled' => EnsureRegistrationEnabled::class,
            'not-demo' => EnsureNotDemo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

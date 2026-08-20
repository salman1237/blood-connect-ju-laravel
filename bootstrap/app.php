<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'onboarded' => EnsureOnboardingComplete::class,
        ]);
        // Both appended (not prepended) — SetLocale reads the session, which
        // only exists after the web group's own StartSession middleware has
        // run, so it can't go before that. EnsureAccountIsActive is global
        // (not just on gated route groups) so a session started before
        // deactivation is cut off on its very next request anywhere.
        $middleware->web(append: [SetLocale::class, EnsureAccountIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

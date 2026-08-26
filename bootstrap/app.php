<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAccountIsActiveApi;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureOnboardingCompleteApi;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'onboarded' => EnsureOnboardingComplete::class,
            'onboarded.api' => EnsureOnboardingCompleteApi::class,
            'active.api' => EnsureAccountIsActiveApi::class,
        ]);
        // Both appended (not prepended) — SetLocale reads the session, which
        // only exists after the web group's own StartSession middleware has
        // run, so it can't go before that. EnsureAccountIsActive is global
        // (not just on gated route groups) so a session started before
        // deactivation is cut off on its very next request anywhere.
        $middleware->web(append: [SetLocale::class, EnsureAccountIsActive::class]);

        // When hosted behind a reverse proxy that terminates TLS (e.g.
        // Traefik/Dokploy) and forwards to this container over plain HTTP,
        // Laravel needs to trust the proxy's X-Forwarded-* headers to know
        // the original request was HTTPS -- otherwise url()/asset()/Vite
        // all generate http:// links, which browsers block as mixed content
        // on a page actually served over https://, breaking every asset.
        // Safe to trust all here since only the proxy can reach this
        // container; it isn't exposed directly.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

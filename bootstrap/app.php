<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/postmark/inbound',
        ]);

        // Railway (and most PaaS hosts) terminate TLS at their edge proxy and
        // forward requests to the app as plain HTTP, so without this Laravel
        // never sees the request as secure and generates http:// asset/URL
        // links - which browsers then block as mixed content on an https://
        // page. The proxy is Railway's own edge, not an arbitrary client, so
        // trusting it here is safe.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

<?php

use App\Http\Middleware\AuthenticateMobile;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnforceWebAbsoluteSessionLifetime;
use App\Http\Middleware\RequireCapability;
use App\Http\ProblemDetailsResponder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CorrelationIdMiddleware::class);
        $middleware->web(append: [EnforceWebAbsoluteSessionLifetime::class]);
        $middleware->alias([
            'mobile.auth' => AuthenticateMobile::class,
            'capability' => RequireCapability::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ProblemDetailsResponder::fromThrowable($throwable, $request);
        });
    })
    ->create();

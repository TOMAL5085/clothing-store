<?php

use App\Http\Middleware\RequestId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(RequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Production-safe error contract for unexpected failures: keep the
        // framework's status codes and messages for validation, auth, 404
        // and throttling responses, but never leak stack traces, paths or
        // environment values from a 500. Local/test diagnostics (debug
        // mode) are untouched.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || app()->hasDebugModeEnabled()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            if (method_exists($e, 'getStatusCode') && $e->getStatusCode() !== 500) {
                return null;
            }

            $requestId = Context::get('request_id') ?? $request->attributes->get('request_id');

            $response = response()->json([
                'message' => 'Server error.',
                'code' => 'INTERNAL_ERROR',
                'request_id' => $requestId,
            ], 500);
            $response->headers->set('X-Request-ID', (string) $requestId);

            return $response;
        });
    })->create();

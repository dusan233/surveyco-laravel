<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'clerkauthentication' => \App\Http\Middleware\ClerkAuthentication::class,
            'emailVerified' => \App\Http\Middleware\EmailAddressVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (PDOException $e) {
            return response()->json([
                "message" => "Something went wrong",
                "status" => 500
            ], 500);
        });
        $exceptions->renderable(function (Exception $e) {
            $status = $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            return response()->json([
                "message" => $e->getMessage() ?: "Something went wrong",
                "status" => $status,
            ], $status);
        });
    })->create();

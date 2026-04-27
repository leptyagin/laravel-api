<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'force.json' => ForceJsonResponse::class,
            'log.api' => LogApiRequests::class,
            'verified' => EnsureEmailVerified::class,
        ]);
    })

    ->withExceptions(function ($exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {

                $status = match (true) {
                    $e instanceof ValidationException => 422,
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                    method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                    default => 500,
                };

                $isClientError = $status >= 400 && $status < 500;

                $message = ($isClientError || config('app.debug'))
                    ? $e->getMessage()
                    : 'Server Error';

                $data = [
                    'success' => false,
                    'message' => $message ?: 'An error occurred',
                ];

                if ($e instanceof ValidationException) {
                    $data['errors'] = $e->errors();
                }

                return response()->json($data, $status);
            }
        });
    })->create();

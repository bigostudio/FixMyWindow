<?php

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Exceptions\RateLimitException;
use App\Http\Middleware\ForceJson;
use App\Http\Middleware\ResponseEnvelope;
use App\Http\Middleware\RoleGuard;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(ForceJson::class);
        $middleware->append(ResponseEnvelope::class);
        $middleware->alias(['role' => RoleGuard::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => ['errors' => $e->errors()],
                'message' => 'Validation failed',
            ], 422);
        });

        $exceptions->render(function (BusinessRuleException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (RateLimitException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], 429);
        });

        $exceptions->render(function (ConflictException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], 409);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Unauthenticated',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Forbidden',
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Resource not found',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Resource not found',
            ], 404);
        });

        $exceptions->render(function (Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'An unexpected error occurred.',
            ], 500);
        });
    })->create();

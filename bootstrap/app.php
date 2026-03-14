<?php

use App\Enums\ErrorCode;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e) {
            return response()->json(data: [
                'status' => 'error',
                'code' => ErrorCode::INTERNAL_ERROR->value,
                'message' => 'Não autorizado. Token ausente ou inválido.',
                'details' => []
            ], status: 401);
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => ErrorCode::VALIDATION_FAILED->value,
                'message' => 'Os dados fornecidos são inválidos.',
                'details' => $e->errors(),
            ], status: 400);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => ErrorCode::NOT_FOUND->value,
                'message' => 'O recurso solicitado não foi encontrado.',
                'details' => []
            ], status: 404);
        });

        $exceptions->render(function (Throwable $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => ErrorCode::INTERNAL_ERROR->value,
                'message' => $e->getMessage(),
                'details' => []
            ], status: $statusCode);
        });
    })->create();

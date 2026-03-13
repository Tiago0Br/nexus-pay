<?php

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
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e) {
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => "VALIDATION_FAILED",
                'message' => 'Os dados fornecidos são inválidos.',
                'details' => $e->errors(),
            ], status: 400);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => "NOT_FOUND",
                'message' => 'O recurso solicitado não foi encontrado.',
                'details' => []
            ], status: 404);
        });

        $exceptions->render(function (Throwable $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 400;
            return response()->json(data: [
                'status' => 'ERROR',
                "code" => "INTERNAL_ERROR",
                'message' => $e->getMessage(),
                'details' => []
            ], status: $statusCode);
        });
    })->create();

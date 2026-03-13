<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API is working!',
        'version' => '1.0.0',
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'store']);

    Route::group(['prefix' => '/transactions'], function () {
        Route::post('/', [TransactionController::class, 'store']);
        Route::patch('/{transaction}/charge_back', [TransactionController::class, 'chargeBack']);
    });
});

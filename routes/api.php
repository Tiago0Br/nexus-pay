<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API is working!',
        'version' => '1.0.0',
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

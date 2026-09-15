<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\InterestController;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'qismat-api', 'version' => 'v1']));

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/matches', [MatchController::class, 'index']);

        Route::get('/interests', [InterestController::class, 'index']);
        Route::post('/interests', [InterestController::class, 'store']);
        Route::post('/interests/{interest}/respond', [InterestController::class, 'respond']);
    });
});

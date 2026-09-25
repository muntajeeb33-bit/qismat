<?php

use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminProfileModerationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FirebaseAuthController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\PartnerPreferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProfileOnboardingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'data' => ['service' => 'qismat-api', 'version' => 'v1'],
        'message' => null,
    ]));

    Route::prefix('auth')->group(function () {
        Route::post('/register', [FirebaseAuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('/login', [FirebaseAuthController::class, 'login'])->middleware('throttle:20,1');
        Route::post('/password-reset', [FirebaseAuthController::class, 'passwordReset'])->middleware('throttle:10,1');
        Route::post('/firebase', [FirebaseAuthController::class, 'exchange'])
            ->middleware(['firebase.auth', 'throttle:20,1']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::prefix('admin')->middleware(['verified', 'admin'])->group(function () {
            Route::get('/dashboard', AdminDashboardController::class);
            Route::get('/profiles', [AdminProfileModerationController::class, 'index']);
            Route::post('/profiles/{profile}/review', [AdminProfileModerationController::class, 'review']);
        });

        Route::middleware('verified')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::get('/profile/onboarding-status', [ProfileOnboardingController::class, 'status']);
            Route::post('/profile/submit', [ProfileOnboardingController::class, 'submit']);
            Route::put('/profile/discovery', [ProfileOnboardingController::class, 'discovery']);
            Route::get('/profile/partner-preferences', [PartnerPreferenceController::class, 'show']);
            Route::put('/profile/partner-preferences', [PartnerPreferenceController::class, 'update']);

            Route::get('/matches', [MatchController::class, 'index']);

            Route::get('/interests', [InterestController::class, 'index']);
            Route::post('/interests', [InterestController::class, 'store']);
            Route::post('/interests/{interest}/respond', [InterestController::class, 'respond']);
        });
    });
});

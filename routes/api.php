<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\SurveyController;
use App\Http\Controllers\Api\Mobile\SurveyEntryController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/surveys', [SurveyController::class, 'index']);
        Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
        Route::post('/surveys/{survey}/entries', [SurveyEntryController::class, 'store']);
        Route::get('/notifications', [NotificationController::class, 'index']);
    });
});

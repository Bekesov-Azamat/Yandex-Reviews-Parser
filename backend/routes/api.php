<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ParseAttemptController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/organization', [OrganizationController::class, 'show']);
    Route::post('/organization', [OrganizationController::class, 'store'])->middleware('throttle:3,1');
    Route::get('/organizations', [OrganizationController::class, 'index']);

    Route::get('/organization/parse-attempts', [ParseAttemptController::class, 'index']);
    Route::get('/organization/reviews', [ReviewController::class, 'index']);
    Route::get('/organizations/{organization}', [OrganizationController::class, 'showById']);
    Route::get('/organizations/{organization}/reviews', [ReviewController::class, 'indexByOrganization']);
    Route::get('/organizations/{organization}/parse-attempts', [ParseAttemptController::class, 'indexByOrganization']);
});

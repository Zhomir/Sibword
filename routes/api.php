<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\HealthApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthApiController::class);

    Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me']);
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    });

});

<?php

use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Customer Auth ────────────────────────────────────────────────
    Route::post('auth/send-otp',    [CustomerAuthController::class, 'sendOtp']);
    Route::post('auth/verify-otp',  [CustomerAuthController::class, 'verifyOtp']);
    Route::post('auth/refresh-token', [CustomerAuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
    });

    // ─── Admin Auth ───────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::post('auth/login',         [AdminAuthController::class, 'login']);
        Route::post('auth/refresh-token', [AdminAuthController::class, 'refresh']);

        Route::middleware('auth:admin')->group(function () {
            Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
            Route::post('auth/change-password', [AdminAuthController::class, 'changePassword']);
        });
    });
});

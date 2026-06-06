<?php

use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Customer Auth ────────────────────────────────────────────────
    Route::post('auth/send-otp',      [CustomerAuthController::class, 'sendOtp']);
    Route::post('auth/verify-otp',    [CustomerAuthController::class, 'verifyOtp']);
    Route::post('auth/refresh-token', [CustomerAuthController::class, 'refresh']);
    Route::post('auth/b2b/register',  [CustomerAuthController::class, 'registerB2B']);
    Route::post('auth/b2b/login',     [CustomerAuthController::class, 'loginB2B']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
        Route::put('profile',     [CustomerProfileController::class, 'update']);
    });

    // ─── Admin Auth ───────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::post('auth/login',         [AdminAuthController::class, 'login']);
        Route::post('auth/refresh-token', [AdminAuthController::class, 'refresh']);

        Route::middleware('auth:admin')->group(function () {
            Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
            Route::post('auth/change-password', [AdminAuthController::class, 'changePassword']);

            // User management
            Route::middleware('role:super_admin,ops_admin')->group(function () {
                Route::post('users', [AdminUserController::class, 'store']);
            });
        });
    });
});

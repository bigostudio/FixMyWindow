<?php

use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\ContactController as CustomerContactController;
use App\Http\Controllers\Customer\EnquiryController as CustomerEnquiryController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Customer Auth ────────────────────────────────────────────────
    Route::post('contact',             [CustomerContactController::class, 'store']);

    Route::post('auth/send-otp',      [CustomerAuthController::class, 'sendOtp']);
    Route::post('auth/register',      [CustomerAuthController::class, 'register']);
    Route::post('auth/verify-otp',    [CustomerAuthController::class, 'verifyOtp']);
    Route::post('auth/refresh-token', [CustomerAuthController::class, 'refresh']);
    Route::post('auth/b2b/register',  [CustomerAuthController::class, 'registerB2B']);
    Route::post('auth/b2b/login',     [CustomerAuthController::class, 'loginB2B']);

    // Email verification (public — no auth required)
    Route::get('auth/email/verify/{token}',   [CustomerAuthController::class, 'verifyEmail']);
    Route::post('auth/email/resend',          [CustomerAuthController::class, 'resendVerification']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout',    [CustomerAuthController::class, 'logout']);
        Route::put('profile',         [CustomerProfileController::class, 'update']);

        // ─── Enquiries ────────────────────────────────────────────────
        Route::post('enquiries/book',      [CustomerEnquiryController::class, 'book']);
        Route::get('enquiries',            [CustomerEnquiryController::class, 'index']);
        Route::get('enquiries/{id}',       [CustomerEnquiryController::class, 'show']);
    });

    // ─── Admin Auth ───────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::post('auth/register',      [AdminAuthController::class, 'register']);
        Route::post('auth/login',         [AdminAuthController::class, 'login']);
        Route::post('auth/refresh-token', [AdminAuthController::class, 'refresh']);

        // Email verification for admin users (public)
        Route::get('auth/email/verify/{token}', [AdminAuthController::class, 'verifyEmail']);

        Route::middleware('auth:admin')->group(function () {
            Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
            Route::post('auth/change-password', [AdminAuthController::class, 'changePassword']);
            Route::post('auth/email/resend',    [AdminAuthController::class, 'resendVerification']);

            // User management
            Route::middleware('role:super_admin,ops_admin')->group(function () {
                Route::post('users',               [AdminUserController::class, 'store']);
                Route::put('users/{id}/approve',   [AdminUserController::class, 'approve']);
                Route::put('users/{id}/reject',    [AdminUserController::class, 'reject']);
            });

            // Bookings — accessible by admin, ops admin, and project manager (supervisor)
            Route::middleware('role:super_admin,ops_admin,project_manager')->group(function () {
                Route::get('enquiries', [AdminEnquiryController::class, 'index']);
            });
        });
    });
});

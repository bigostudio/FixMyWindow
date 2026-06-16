<?php

use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\ContactController as CustomerContactController;
use App\Http\Controllers\Customer\EnquiryController as CustomerEnquiryController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlueprintController as AdminBlueprintController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Customer\BlueprintController as CustomerBlueprintController;
use App\Http\Controllers\Admin\MeasurementController as AdminMeasurementController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Contact ──────────────────────────────────────────────────────────
    Route::post('contact', [CustomerContactController::class, 'store']);

    // ─── Customer Auth ────────────────────────────────────────────────────
    Route::post('auth/send-otp',      [CustomerAuthController::class, 'sendOtp']);
    Route::post('auth/register',      [CustomerAuthController::class, 'register']);
    Route::post('auth/verify-otp',    [CustomerAuthController::class, 'verifyOtp']);
    Route::post('auth/refresh-token', [CustomerAuthController::class, 'refresh']);
    Route::post('auth/b2b/register',  [CustomerAuthController::class, 'registerB2B']);
    Route::post('auth/b2b/login',     [CustomerAuthController::class, 'loginB2B']);
    Route::get('auth/email/verify/{token}', [CustomerAuthController::class, 'verifyEmail']);
    Route::post('auth/email/resend',        [CustomerAuthController::class, 'resendVerification']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
        Route::put('profile',      [CustomerProfileController::class, 'update']);

        // ─── Customer Enquiries ───────────────────────────────────────────
        Route::post('enquiries',       [CustomerEnquiryController::class, 'create']);
        Route::get('enquiries',        [CustomerEnquiryController::class, 'index']);
        Route::get('enquiries/{id}',   [CustomerEnquiryController::class, 'show']);
        Route::get('addresses',        [CustomerEnquiryController::class, 'addresses']);

        // ─── Blueprints (saved building templates, reusable across enquiries) ──
        Route::get('blueprints',         [CustomerBlueprintController::class, 'index']);
        Route::post('blueprints',        [CustomerBlueprintController::class, 'store']);
        Route::get('blueprints/{id}',    [CustomerBlueprintController::class, 'show']);
        Route::put('blueprints/{id}',    [CustomerBlueprintController::class, 'update']);
        Route::delete('blueprints/{id}', [CustomerBlueprintController::class, 'destroy']);
    });

    // ─── Admin ────────────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {

        // Admin Auth (public)
        Route::post('auth/register',      [AdminAuthController::class, 'register']);
        Route::post('auth/login',         [AdminAuthController::class, 'login']);
        Route::post('auth/refresh-token', [AdminAuthController::class, 'refresh']);
        Route::get('auth/email/verify/{token}', [AdminAuthController::class, 'verifyEmail']);

        Route::middleware('auth:admin')->group(function () {
            Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
            Route::post('auth/change-password', [AdminAuthController::class, 'changePassword']);
            Route::post('auth/email/resend',    [AdminAuthController::class, 'resendVerification']);

            // ─── User Management (ops_admin only) ────────────────────────
            Route::middleware('role:ops_admin')->group(function () {
                Route::get('users/staff',        [AdminUserController::class, 'staff']);
                Route::get('users/pending',      [AdminUserController::class, 'pending']);
                Route::get('users/by-role',      [AdminUserController::class, 'byRole']);
                Route::post('users',             [AdminUserController::class, 'store']);
                Route::put('users/{id}/approve', [AdminUserController::class, 'approve']);
                Route::put('users/{id}/reject',  [AdminUserController::class, 'reject']);
            });

            // ─── Enquiries — all internal roles ──────────────────────────
            Route::middleware('role:ops_admin,ops_manager,supervisor,technician')->group(function () {
                Route::get('enquiry-statuses',                     [AdminEnquiryController::class, 'statuses']);
                Route::get('enquiries',                            [AdminEnquiryController::class, 'index']);
                Route::get('enquiries/{id}',                       [AdminEnquiryController::class, 'show']);
                Route::put('enquiries/{id}/status',                [AdminEnquiryController::class, 'updateStatus']);
                Route::get('enquiries/{id}/team',                  [AdminEnquiryController::class, 'team']);
                Route::post('customers/{id}/blueprints',            [AdminBlueprintController::class, 'store']);
                Route::get('customers/{id}/blueprints',             [AdminBlueprintController::class, 'byCustomer']);
                Route::get('enquiries/{id}/blueprint',              [AdminBlueprintController::class, 'showByEnquiry']);
                Route::put('blueprints/{id}',                       [AdminBlueprintController::class, 'update']);
                Route::delete('blueprints/{id}',                    [AdminBlueprintController::class, 'destroy']);
            });

            // ─── Enquiry ops — ops_admin and ops_manager only ─────────────
            Route::middleware('role:ops_admin,ops_manager')->group(function () {
                Route::post('enquiries/{id}/initiate-survey',      [AdminEnquiryController::class, 'initiateSurvey']);
                Route::put('enquiries/{id}/assign',                [AdminEnquiryController::class, 'assignStaff']);
            });

            // ─── Surveys — ops_admin and ops_manager only ─────────────────
            Route::middleware('role:ops_admin,ops_manager')->group(function () {
                Route::post('surveys',                   [AdminSurveyController::class, 'store']);
                Route::get('surveys',                    [AdminSurveyController::class, 'index']);
                Route::get('surveys/{id}',               [AdminSurveyController::class, 'show']);
                Route::put('surveys/{id}/checklist',     [AdminSurveyController::class, 'updateChecklist']);
                Route::put('surveys/{id}/gonogo',        [AdminSurveyController::class, 'submitGoNoGo']);
            });

            // ─── Measurements — all internal roles ───────────────────────
            Route::middleware('role:ops_admin,ops_manager,supervisor,technician')->group(function () {
                Route::post('measurements',       [AdminMeasurementController::class, 'store']);
                Route::get('measurements/{id}',   [AdminMeasurementController::class, 'show']);
                Route::put('measurements/{id}',   [AdminMeasurementController::class, 'update']);
            });
        });
    });
});

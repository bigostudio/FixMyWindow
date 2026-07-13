<?php

use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\Msg91AuthController as CustomerMsg91AuthController;
use App\Http\Controllers\Customer\ContactController as CustomerContactController;
use App\Http\Controllers\Customer\EnquiryController as CustomerEnquiryController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlueprintController as AdminBlueprintController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\EnquiryNoteController as AdminEnquiryNoteController;
use App\Http\Controllers\Customer\BlueprintController as CustomerBlueprintController;
use App\Http\Controllers\Admin\ProjectStageController as AdminProjectStageController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Hosting routes every request under /api into this app's index.php (there is
// no static-file passthrough at the webserver level), so uploaded files must
// be served through Laravel rather than relying on Apache to hand them off.
Route::get('uploads/{path}', function (string $path) {
    if (! Storage::disk('cloud')->exists($path)) {
        abort(404);
    }

    return Storage::disk('cloud')->response($path);
})->where('path', '.*');

Route::prefix('v1')->group(function () {

    // ─── Contact ──────────────────────────────────────────────────────────
    Route::post('contact', [CustomerContactController::class, 'store']);

    // ─── Customer Auth (MSG91 widget OTP) ────────────────────────────────
    Route::post('auth/msg91/send-otp',   [CustomerMsg91AuthController::class, 'sendOtp']);
    Route::post('auth/msg91/retry-otp',  [CustomerMsg91AuthController::class, 'retryOtp']);
    Route::post('auth/msg91/verify-otp', [CustomerMsg91AuthController::class, 'verifyOtp']);

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
        Route::get('blueprints',                                   [CustomerBlueprintController::class, 'index']);
        Route::post('blueprints',                                  [CustomerBlueprintController::class, 'store']);
        Route::get('blueprints/{id}',                              [CustomerBlueprintController::class, 'show']);
        Route::put('blueprints/{id}',                              [CustomerBlueprintController::class, 'update']);
        Route::delete('blueprints/{id}',                           [CustomerBlueprintController::class, 'destroy']);
        Route::post('enquiries/{id}/blueprint/photos',              [CustomerBlueprintController::class, 'storePhoto']);
        Route::get('enquiries/{id}/blueprint/photos',              [CustomerBlueprintController::class, 'indexPhoto']);
        Route::delete('enquiries/{id}/blueprint/photos/{photoId}', [CustomerBlueprintController::class, 'destroyPhoto']);
    });

    // ─── Admin ────────────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {

        // Admin Auth (public)
        Route::post('auth/register',      [AdminAuthController::class, 'register']);
        Route::post('auth/login',         [AdminAuthController::class, 'login']);
        Route::post('auth/refresh-token', [AdminAuthController::class, 'refresh']);
        Route::get('auth/email/verify/{token}', [AdminAuthController::class, 'verifyEmail']);

        Route::middleware('auth:admin')->group(function () {
            Route::get('dashboard', [AdminDashboardController::class, 'index']);

            Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
            Route::post('auth/change-password', [AdminAuthController::class, 'changePassword']);
            Route::post('auth/email/resend',    [AdminAuthController::class, 'resendVerification']);

            // ─── User Management (ops_admin only) ────────────────────────
            Route::middleware('role:ops_admin')->group(function () {
                Route::get('users/staff',        [AdminUserController::class, 'staff']);
                Route::get('users/pending',      [AdminUserController::class, 'pending']);
                Route::post('users',             [AdminUserController::class, 'store']);
                Route::put('users/{id}/approve', [AdminUserController::class, 'approve']);
                Route::put('users/{id}/reject',  [AdminUserController::class, 'reject']);
            });

            // ─── Staff by role (ops_admin/ops_manager see everyone; supervisor sees technicians + self; technician sees only self) ───
            Route::middleware('role:ops_admin,ops_manager,supervisor,technician')->group(function () {
                Route::get('users/by-role', [AdminUserController::class, 'byRole']);
            });

            // ─── Enquiries — all internal roles ──────────────────────────
            Route::middleware('role:ops_admin,ops_manager,supervisor,technician')->group(function () {
                Route::get('customers',                            [AdminCustomerController::class, 'index']);
                Route::get('enquiry-statuses',                     [AdminEnquiryController::class, 'statuses']);
                Route::get('enquiries',                            [AdminEnquiryController::class, 'index']);
                Route::get('enquiries/{id}',                       [AdminEnquiryController::class, 'show']);
                Route::put('enquiries/{id}/status',                [AdminEnquiryController::class, 'updateStatus']);
                Route::get('enquiries/{id}/team',                  [AdminEnquiryController::class, 'team']);
                Route::get('enquiries/{id}/notes',                 [AdminEnquiryNoteController::class, 'index']);
                Route::post('enquiry-notes',                       [AdminEnquiryNoteController::class, 'store']);
                Route::put('enquiries/{id}/blueprint',             [AdminEnquiryController::class, 'linkBlueprint']);
                Route::post('customers/{id}/blueprints',            [AdminBlueprintController::class, 'store']);
                Route::get('customers/{id}/blueprints',             [AdminBlueprintController::class, 'byCustomer']);
                Route::get('enquiries/{id}/blueprint',              [AdminBlueprintController::class, 'showByEnquiry']);
                Route::put('blueprints/{id}',                       [AdminBlueprintController::class, 'update']);
                Route::delete('blueprints/{id}',                    [AdminBlueprintController::class, 'destroy']);
                Route::post('enquiries/{id}/blueprint/photos',              [AdminBlueprintController::class, 'storePhoto']);
                Route::get('enquiries/{id}/blueprint/photos',               [AdminBlueprintController::class, 'indexPhoto']);
                Route::delete('enquiries/{id}/blueprint/photos/{photoId}',  [AdminBlueprintController::class, 'destroyPhoto']);
            });

            // ─── Enquiry ops — ops_admin and ops_manager only ─────────────
            Route::middleware('role:ops_admin,ops_manager')->group(function () {
                Route::put('enquiries/{id}/assign',                [AdminEnquiryController::class, 'assignStaff']);
                Route::delete('enquiries/{id}',                    [AdminEnquiryController::class, 'destroy']);
            });

            // ─── Surveys — ops_admin and ops_manager only ─────────────────
            Route::middleware('role:ops_admin,ops_manager')->group(function () {
                Route::post('surveys',                   [AdminSurveyController::class, 'store']);
                Route::get('surveys',                    [AdminSurveyController::class, 'index']);
                Route::get('surveys/{id}',               [AdminSurveyController::class, 'show']);
                Route::put('surveys/{id}/gonogo',        [AdminSurveyController::class, 'submitGoNoGo']);
            });

            // ─── Survey checklist — surveyor (supervisor) also allowed ────
            Route::middleware('role:ops_admin,ops_manager,supervisor')->group(function () {
                Route::put('surveys/{id}/checklist',     [AdminSurveyController::class, 'updateChecklist']);
            });

            // ─── Project Stages (shared towers JSON across measurement / quality_check / installation) — all internal roles ───
            Route::middleware('role:ops_admin,ops_manager,supervisor,technician')->group(function () {
                Route::post('project-stages',         [AdminProjectStageController::class, 'store']);
                Route::get('project-stages/{id}',     [AdminProjectStageController::class, 'show']);
                Route::put('project-stages/{id}',     [AdminProjectStageController::class, 'update']);
                Route::delete('project-stages/{id}',  [AdminProjectStageController::class, 'destroy']);
            });
        });
    });
});

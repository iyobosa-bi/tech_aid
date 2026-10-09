<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'cache.headers:no_store'])->group(function () {
    // No self-registration: an Admin manages accounts (Admin → Users; bulk import later).

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('login/otp/verify', [AuthenticatedSessionController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('login.otp.verify');

    Route::post('login/otp/resend', [AuthenticatedSessionController::class, 'resendOtp'])
        ->middleware('throttle:3,10')
        ->name('login.otp.resend');

    Route::post('login/otp/cancel', [AuthenticatedSessionController::class, 'cancelOtp'])
        ->name('login.otp.cancel');

    // Forgot password: email → 6-digit code → new password (PasswordResetController, PasswordService).
    // Code requests: 3 per 10 minutes per email and 10 per IP ('password-reset-codes', AppServiceProvider).
    Route::get('forgot-password', [PasswordResetController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetController::class, 'sendCode'])
        ->middleware('throttle:password-reset-codes')
        ->name('password.email');

    Route::get('forgot-password/code', [PasswordResetController::class, 'showCode'])
        ->name('password.code');

    Route::post('forgot-password/code', [PasswordResetController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('password.code.verify');

    Route::post('forgot-password/resend', [PasswordResetController::class, 'resendCode'])
        ->middleware('throttle:password-reset-codes')
        ->name('password.code.resend');

    Route::get('reset-password', [PasswordResetController::class, 'edit'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::middleware(['auth', 'cache.headers:no_store'])->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

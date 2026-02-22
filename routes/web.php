<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\PageController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'calendar']);
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/register', [PageController::class, 'register']);
Route::get('/verify-email', [PageController::class, 'verifyEmail'])->middleware('auth');
Route::get('/forgot-password', [PageController::class, 'forgotPassword']);
Route::get('/reset-password', [PageController::class, 'resetPassword']);

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/?verified=1');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::prefix('api')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification']);

        Route::middleware('verified')->group(function (): void {
            Route::get('/events', [EventController::class, 'list']);
            Route::post('/events', [EventController::class, 'create']);
            Route::post('/events/join', [EventController::class, 'join']);
            Route::post('/events/leave', [EventController::class, 'leave']);
            Route::post('/events/delete', [EventController::class, 'delete']);
        });
    });
});

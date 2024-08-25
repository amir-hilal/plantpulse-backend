<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

Route::middleware('auth:api')->group(function () {
    Route::post('email/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend');
});

Route::post('password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.reset');
Route::post('password/reset', [PasswordResetController::class, 'reset']);
Route::middleware('auth:api')->post('refresh-token', [AuthController::class, 'refreshToken']);

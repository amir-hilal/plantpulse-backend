<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    // ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class]);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    // ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class]);

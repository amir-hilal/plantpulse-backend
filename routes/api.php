<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommunityPostController;
use App\Http\Controllers\FriendController;
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
Route::post('password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.reset');
Route::post('password/reset', [PasswordResetController::class, 'reset']);
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->name('/verification.resend');
    Route::put('/users/{username}', [UserController::class, 'update']);
    Route::post('/upload/profile-photo', [UserController::class, 'uploadProfilePhoto']);
    Route::post('/posts', [CommunityPostController::class, 'createPost']);
    Route::get('/posts', [CommunityPostController::class, 'fetchAllPosts']);
    Route::get('/posts/{username}', [CommunityPostController::class, 'fetchPostsByUsername']);
    Route::get('/friends', [FriendController::class, 'listFriends']);
    Route::post('/friends/request', [FriendController::class, 'sendRequest']);
    Route::post('/friends/accept/{id}', [FriendController::class, 'acceptRequest']);
    Route::post('/friends/decline/{id}', [FriendController::class, 'declineRequest']);
    Route::delete('/friends/remove/{id}', [FriendController::class, 'removeFriend']);
    Route::get('/friend-requests', [FriendController::class, 'listRequests']);




});

Route::get('/users/{username}', [UserController::class, 'show']);

<?php
use App\Http\Controllers\GardenController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommunityPostController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\PlantTimelineController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TutorialController;
use App\Http\Controllers\TutorialCommentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WateringEventController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\OpenWeatherController;
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
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);


Route::get('/weather', [OpenWeatherController::class, 'currentWeather']);
Route::get('/forecast', [OpenWeatherController::class, 'forecastWeather']);

Route::get('/admin/dashboard/stats', [AdminDashboardController::class, 'getStats'])
    ->middleware('auth:api', 'admin');

Route::prefix('tutorials')->group(function () {
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::post('/', [TutorialController::class, 'store']);
        Route::put('/{id}', [TutorialController::class, 'update']);
        Route::delete('/{id}', [TutorialController::class, 'destroy']);
    });

    Route::get('/', [TutorialController::class, 'index']);
    Route::get('/search', [TutorialController::class, 'search']);  // New search route
    Route::get('/{id}', [TutorialController::class, 'show']);
    Route::post('/{id}/comments', [TutorialCommentController::class, 'store']);
    Route::delete('/comments/{id}', [TutorialCommentController::class, 'destroy']);
    Route::get('/{id}/comments', [TutorialCommentController::class, 'index']);
});

Route::middleware('auth:api')->group(function () {
    Broadcast::routes();
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    Route::post('/upload/profile-photo', [UserController::class, 'uploadProfilePhoto']);
    Route::post('profile/update-cover-photo/{id}', [UserController::class, 'updateCoverPhoto']);

    Route::prefix('posts')->group(function () {
        Route::post('/', [CommunityPostController::class, 'createPost']);
        Route::get('/friends/all', [CommunityPostController::class, 'fetchFriendsPosts']);
        Route::get('/{username}', [CommunityPostController::class, 'fetchPostsByUsername']);
        Route::get('/details/{id}', [CommunityPostController::class, 'fetchPostById']);
        Route::delete('/{id}', [CommunityPostController::class, 'deletePost']);
        Route::get('/details/{id}/comments', [CommentController::class, 'fetchComments']);
        Route::post('/details/{id}/comments', [CommentController::class, 'addComment']);
        Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    });


    Route::prefix('friends')->group(function () {
        Route::get('/{username}', [FriendController::class, 'listFriends']);
        Route::post('/request', [FriendController::class, 'sendRequest']);
        Route::post('/accept/{id}', [FriendController::class, 'acceptRequest']);
        Route::post('/decline/{id}', [FriendController::class, 'declineRequest']);
        Route::delete('/remove/{id}', [FriendController::class, 'removeFriend']);
    });

    Route::get('/friend-requests', [FriendController::class, 'listRequests']);


    Route::prefix('users')->group(function () {
        Route::put('/{username}', [UserController::class, 'update']);
        Route::get('/all', [UserController::class, 'index']);
        Route::get('/all/search', [UserController::class, 'search']);
        Route::get('/show/{username}', [UserController::class, 'show']);
        Route::get('/watering-schedules/today', [WateringEventController::class, 'getUserTodayWateringSchedules']);
        Route::get('/watering-schedules/week', [WateringEventController::class, 'getUserWeekWateringSchedules']);

    });

    Route::prefix('garden')->group(function () {
        Route::get('/', [GardenController::class, 'index']);
        Route::get('/{gardenId}/plants', [PlantController::class, 'index']);
        Route::post('/', [GardenController::class, 'store']);
        Route::get('/{id}', [GardenController::class, 'show']);
        Route::post('/{id}', [GardenController::class, 'update']);
        Route::delete('/{id}', [GardenController::class, 'destroy']);
        Route::post('/update-image/{id}', [GardenController::class, 'updateImage']);
    });

    Route::prefix('plants')->group(function () {
        Route::post('', [PlantController::class, 'store']);
        Route::get('/{id}', [PlantController::class, 'show']);
        Route::post('/{id}', [PlantController::class, 'update']);
        Route::delete('/{id}', [PlantController::class, 'destroy']);
        Route::get('/{plantId}/timelines', [PlantTimelineController::class, 'index']);
        Route::post('/{plantId}/timelines', [PlantTimelineController::class, 'store']);
        Route::delete('/timelines/{id}', [PlantTimelineController::class, 'destroy']);
        Route::get('/{plant}/watering-events', [WateringEventController::class, 'index']);
        Route::put('/{plant}/watering-events/{event}', [WateringEventController::class, 'markComplete']);

    });

    Route::prefix('timelines')->group(function () {


        Route::post('/', [PlantTimelineController::class, 'store']);
        Route::get('/{id}', [PlantTimelineController::class, 'show']);
        Route::put('/{id}', [PlantTimelineController::class, 'update']);
        Route::delete('/{id}', [PlantTimelineController::class, 'destroy']);
    });


    Route::get('chats/{receiver_id}', [ChatController::class, 'getMessages']);
    Route::post('chats/send', [ChatController::class, 'sendMessage']);
    Route::get('chats/users/conversations', [ChatController::class, 'getConversations']);

});

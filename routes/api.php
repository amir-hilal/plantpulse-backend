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


Route::get('/weather', function () {
    $lat = request('lat');
    $lon = request('lon');

    $apiKey = env('OPENWEATHER_API_KEY');
    $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

    $response = Http::withOptions([
        'verify' => false
    ])->get($weatherUrl);

    if ($response->successful()) {
        return $response->json();
    }

    return response()->json(['error' => 'Failed to fetch current weather data'], 500);
});

Route::get('/forecast', function () {
    $lat = request('lat');
    $lon = request('lon');

    $apiKey = env('OPENWEATHER_API_KEY');
    $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";


    $response = Http::withOptions([
        'verify' => false
    ])->get($forecastUrl);

    if ($response->successful()) {
        return $response->json();
    }

    return response()->json(['error' => 'Failed to fetch weather forecast data'], 500);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    Route::post('/upload/profile-photo', [UserController::class, 'uploadProfilePhoto']);

    // Group routes by posts
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

    // Group routes by friends
    Route::prefix('friends')->group(function () {
        Route::get('/{username}', [FriendController::class, 'listFriends']);
        Route::post('/request', [FriendController::class, 'sendRequest']);
        Route::post('/accept/{id}', [FriendController::class, 'acceptRequest']);
        Route::post('/decline/{id}', [FriendController::class, 'declineRequest']);
        Route::delete('/remove/{id}', [FriendController::class, 'removeFriend']);
    });

    Route::get('/friend-requests', [FriendController::class, 'listRequests']);

    // Group routes by users
    Route::prefix('users')->group(function () {
        Route::put('/{username}', [UserController::class, 'update']);
        Route::get('/all', [UserController::class, 'index']);
        Route::get('/all/search', [UserController::class, 'search']);
        Route::get('/show/{username}', [UserController::class, 'show']);
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
        Route::post('', [PlantController::class, 'store']); // Create a plant
        Route::get('/{id}', [PlantController::class, 'show']); // Get a single plant
        Route::put('/{id}', [PlantController::class, 'update']); // Update a plant
        Route::delete('/{id}', [PlantController::class, 'destroy']); // Delete a plant
        Route::get('/{plantId}/timelines', [PlantTimelineController::class, 'index']); // Get all timeline entries for a plant

    });

    Route::prefix('timelines')->group(function () {

        // Plant Timeline Routes
        Route::post('/', [PlantTimelineController::class, 'store']); // Create a timeline entry
        Route::get('/{id}', [PlantTimelineController::class, 'show']); // Get a single timeline entry
        Route::put('/{id}', [PlantTimelineController::class, 'update']); // Update a timeline entry
        Route::delete('/{id}', [PlantTimelineController::class, 'destroy']); // Delete a timeline entry
    });


});

<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\User;
use App\Models\Tutorial;
use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function getStats(): JsonResponse
    {
        $plantCount = Plant::count();
        $userCount = User::count();
        $tutorialCount = Tutorial::count();
        $postCount = CommunityPost::count();

        return response()->json([
            'plants' => $plantCount,
            'users' => $userCount,
            'tutorials' => $tutorialCount,
            'posts' => $postCount,
        ]);
    }
}

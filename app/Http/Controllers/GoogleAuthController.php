<?php
namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleAuthController extends Controller
{
    // Redirect to Google OAuth
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Handle the callback from Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if the user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // If not, create a new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(24)), // A random password, not used for Google login
                ]);
            }

            // Log in the user
            Auth::login($user, true);

            // Create JWT token for the user
            $token = JWTAuth::fromUser($user);

            // Redirect back to the frontend with the token
            return redirect()->away(env('FRONTEND_URL') . "/auth/google/success?token=$token");
        } catch (\Exception $e) {
            return redirect()->away(env('FRONTEND_URL') . '/auth/google/failure');
        }
    }
}

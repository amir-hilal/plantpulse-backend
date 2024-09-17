<?php
namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->scopes(['openid', 'profile', 'email', 'https://www.googleapis.com/auth/user.birthday.read', 'https://www.googleapis.com/auth/user.phonenumbers.read'])->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $socialiteDriver = Socialite::driver('google')->stateless();
            $socialiteDriver->setHttpClient(new Client(['verify' => false]));


            $googleUser = $socialiteDriver->user();

            Log::info(print_r($googleUser, true));


            $firstName = $googleUser->user['given_name'] ?? null;
            $lastName = $googleUser->user['family_name'] ?? null;
            $email = $googleUser->getEmail();
            $profilePhotoUrl = $googleUser->getAvatar();
            $googleId = $googleUser->getId();
            $username = strtolower($firstName . '.' . $lastName) ?: explode('@', $email)[0];
            $emailVerified = $googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false;
            $emailVerifiedAt = $emailVerified ? now() : null;
            \Log::info('email verification' . $emailVerified . ' , email verified at' . $emailVerifiedAt);
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'google_id' => $googleId,
                    'profile_photo_url' => $profilePhotoUrl,
                    'username' => $username,
                    'phone_number' => $googleUser->user['phoneNumber'] ?? null,
                    'gender' => $googleUser->user['gender'] ?? null,
                    'birthday' => $googleUser->user['birthday'] ?? null,
                    'address' => $googleUser->user['address'] ?? null,
                    'email_verified_at' => $emailVerifiedAt,
                    'password' => Hash::make(Str::random(24)),
                ]
            );


            Auth::login($user, true);


            $token = JWTAuth::fromUser($user);


            return response()->json([
                'token' => $token,
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage());

            return response()->json(['error' => 'Unable to authenticate using Google.'], 500);
        }
    }

}

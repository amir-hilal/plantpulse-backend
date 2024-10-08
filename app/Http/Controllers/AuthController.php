<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $default_profile_picture_url = 'https://plantpulse.s3.me-central-1.amazonaws.com/profile/empty-profile.png';


        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture_url' => $default_profile_picture_url
        ]);

        $token = JWTAuth::fromUser($user);

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Please check your email to verify your account.', 'user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email_or_username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->input('email_or_username');
        $loginType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginType => $loginField,
            'password' => $request->input('password'),
        ];

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 400);
            }

            $user = Auth::user();

            if ($request->header('App-Type') === 'admin' && $user->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized access. Admins only.'], 403);
            }

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }


    public function me(Request $request)
    {
        return response()->json($request->user());
    }

}

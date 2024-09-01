<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class UserController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }
    public function update(Request $request, $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        if ($user->id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'about' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
            'about' => $request->about,
        ]);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }


    public function uploadProfileImage(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|image|max:2048', // Limit image size to 2MB
        ]);

        $file = $request->file('file');
        $path = $file->store('profile', 's3');

        // Ensure the file is publicly accessible
        Storage::disk('s3')->setVisibility($path, 'public');

        $url = Storage::disk('s3')->url($path);

        return response()->json(['url' => $url], 200);
    }
}

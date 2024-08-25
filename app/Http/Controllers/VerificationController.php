<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::find($id);
        if (!$user) {
            return view('verification-error', ['message' => 'User not found.']);
        }

        if ($user->hasVerifiedEmail()) {
            return view('verification-success', ['message' => 'Email already verified.']);
        }

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return view('verification-error', ['message' => 'Invalid verification link.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return view('verification-success', ['message' => 'Email verified successfully.']);
        }

        return view('verification-error', ['message' => 'Failed to verify email.']);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!'], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TutorialComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorialCommentController extends Controller
{
    // Fetch comments for a specific tutorial with pagination
    public function index($tutorialId)
    {
        $comments = TutorialComment::with('user')
            ->where('tutorial_id', $tutorialId)
            ->paginate(10); // Adjust pagination as needed

        return response()->json($comments, 200);
    }

    // Store a new comment for a specific tutorial
    public function store(Request $request, $tutorialId)
    {
        $data = $request->validate([
            'comment_text' => 'required|string',
        ]);

        $data['tutorial_id'] = $tutorialId;
        $data['user_id'] = Auth::id();  // Assuming user is authenticated

        $comment = TutorialComment::create($data);
        $comment->load('user:id,first_name,last_name,profile_photo_url');
        return response()->json($comment, 201);
    }

    // Delete a comment (Owner or Admin only)
    public function destroy($id)
    {
        $comment = TutorialComment::findOrFail($id);

        // Only allow the owner of the comment or an admin to delete
        if (Auth::id() !== $comment->user_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.'], 200);
    }
}

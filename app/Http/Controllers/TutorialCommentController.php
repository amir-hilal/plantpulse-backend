<?php

namespace App\Http\Controllers;

use App\Models\TutorialComment;
use Illuminate\Http\Request;

class TutorialCommentController extends Controller
{
    // Store a new comment for a specific tutorial
    public function store(Request $request, $tutorialId)
    {
        $data = $request->validate([
            'comment' => 'required|string',
        ]);

        $data['tutorial_id'] = $tutorialId;
        $data['user_id'] = auth()->id();  // Assuming user is authenticated

        $comment = TutorialComment::create($data);

        return response()->json($comment, 201);
    }

    // Delete a comment (Admin only)
    public function destroy($id)
    {
        $comment = TutorialComment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.'], 200);
    }
}

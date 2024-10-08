<?php

namespace App\Http\Controllers;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function fetchComments($postId, Request $request)
    {
        $perPage = 10;
        $comments = PostComment::where('post_id', $postId)
            ->with('user')
            ->paginate($perPage);

        return response()->json($comments);
    }

    public function addComment($postId, Request $request)
    {
        $request->validate([
            'comment_text' => 'required|string|max:1000',
        ]);

        $comment = PostComment::create([
            'post_id' => $postId,
            'user_id' => Auth::id(),
            'comment_text' => $request->comment_text,
        ]);

        $comment->load('user:id,first_name,last_name,profile_photo_url');

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    public function destroy($id)
    {
        $comment = PostComment::find($id);

        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        if (auth()->id() !== $comment->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully', 'id' => $id]);
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\PostComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function fetchComments($postId, Request $request)
    {
        $perPage = 10; // Number of comments per page
        $comments = PostComment::where('post_id', $postId)
            ->with('user')
            ->paginate($perPage);

        return response()->json($comments);
    }
}

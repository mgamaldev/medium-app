<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Notifications\CommentReceivedNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Article $article)
    {
        $validateComment = $request->validate([
            'body' => 'required|string',
        ]);

        $validateComment['user_id'] = $request->user()->id;

        $comment = $article->comments()->create($validateComment);

        $recipient = $article->user;

        $recipient->notify(new CommentReceivedNotification($comment));

        return response()->json($article, 200);

    }
}

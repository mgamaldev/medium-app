<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Factories\ArticleFactory;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class ArticleController extends Controller
{
    public function __construct(protected ArticleFactory $articleFactory, protected ArticleRepositoryInterface $articleRepository) {}

    public function store(Request $request)
    {
        try {
            $validateData = $request->validate(
                [
                    'title' => 'required|string|max:255',
                    'body' => 'required|string',
                    'cover_image' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                ]
            );


            $article = $this->articleFactory->create($request->type, $validateData);

            return response()->json($article, 201);

        } catch (\Exception $e) {
            return response()->json(['Error' => $e->getMessage()], 422);
        }
    }

    public function show(Article $article)
    {
        Gate::authorize('view', $article);

        return response()->json($article, 200);
    }

    public function publish(Article $article)
    {
        $article->update([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        $author = $article->user; 
    
        $followers = $author->followers; 

        Notification::send($followers,  ArticlePublishedNotification::class);

        return response()->json([
        'message' => 'Article published successfully',
        'article' => $article
        ], 200);
    }
}

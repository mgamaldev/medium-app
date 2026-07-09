<?php

namespace App\Http\Controllers;

use App\Factories\ArticleFactory;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function __construct(protected ArticleFactory $articleFactory, protected ArticleRepositoryInterface $articleRepository) {}

    public function store(Request $request)
    {
        $validateData = $request->validate(
            [
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'cover_image' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'status' => 'required|string',
            ]
        );
        $validateData['user_id'] = $request->user()->id;

        $article = $this->articleFactory->create($validateData['status'], $validateData);

        return response()->json($article, 201);

    }

    public function show(Article $article)
    {
        Gate::authorize('view', $article);

        return response()->json($article, 200);
    }

    public function publish(Article $article)
    {

        if (Auth::id() !== $article->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $article->publish();

        /** @var User $author */
        $author = $article->user;

        $followers = $author->followers()->get();

        foreach ($followers as $recipient) {
            $recipient->notify(new ArticlePublishedNotification($article));
        }

        return response()->json([
            'message' => 'Article published successfully',
            'article' => $article,
        ], 200);
    }

    public function getTrending(): JsonResponse
    {

        return response()->json([
            'success' => true,
            'message' => 'Trending articles fetched successfully',
            'data' => $this->articleRepository->getTrending(),
        ]);

    }

    public function update(Request $request, Article $article)
    {
        Gate::authorize('update', $article);

        $validateData = $request->validate(
            [
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'cover_image' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'status' => 'required|string',
            ]
        );
        $validateData['user_id'] = $request->user()->id;

        $article = $this->articleRepository->update($article->id, $validateData);

        $updatedArticle = $this->articleRepository->findById($article->id);

        return response()->json($updatedArticle, 200);
    }
}

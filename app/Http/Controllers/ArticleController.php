<?php

namespace App\Http\Controllers;

use App\Factories\ArticleFactory;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\S3StorageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function __construct(protected ArticleFactory $articleFactory, protected ArticleRepositoryInterface $articleRepository) {}

    public function store(StoreArticleRequest $request)
    {
        $validateData = $request->validated();
        $validateData['user_id'] = $request->user()->id;

        $article = $this->articleFactory->create($validateData['status'], $validateData);

        return response()->json($article, 201);
    }

    public function show(Article $article)
    {
        Gate::authorize('view', $article);

        if ($article->cover_image) {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            $article->cover_image = $disk->url($article->cover_image);
        }

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

    public function update(UpdateArticleRequest $request, Article $article)
    {
        Gate::authorize('update', $article);

        $validateData = $request->validated();

        $validateData['user_id'] = $request->user()->id;

        $oldCoverImage = $article->cover_image;

        $this->articleRepository->update($article->id, $validateData);

        if (isset($validateData['cover_image']) && $validateData['cover_image'] !== $oldCoverImage) {
            if ($oldCoverImage) {
                Storage::disk('s3')->delete($oldCoverImage);
            }
        }

        $updatedArticle = $this->articleRepository->findById($article->id);

        return response()->json($updatedArticle, 200);
    }

    public function getPresignedUrl(Request $request, S3StorageService $s3StorageService): JsonResponse
    {
        $validatedData = $request->validate(
            [
                'file_name' => ['required', 'string'],
                'content_type' => ['required', 'string'],
            ]
        );

        $result = $s3StorageService->generatePresignedUrl(
            'covers',
            2048000,
            $validatedData['file_name'],
            $validatedData['content_type']
        );

        return response()->json([
            'upload_url' => $result['upload_url'],
            'file_key' => $result['file_key'],
        ]);
    }
}

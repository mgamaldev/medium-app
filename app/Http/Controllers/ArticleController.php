<?php

namespace App\Http\Controllers;

use App\Factories\ArticleFactory;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function __construct(protected ArticleFactory $articleFactory) {}

    public function store(Request $request)
    {
        try {
            $validateData = $request->validate(
                [
                    'title' => 'required|string|max:255',
                    'body' => 'required|string',
                    'cover_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                ]
            );

            $article = $this->articleFactory->create($request->type, $request->validateData());

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
}

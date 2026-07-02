<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/articles', [ArticleController::class, 'store']);
    Route::post('/articles/{article}/comments', [CommentController::class, 'store']);
});

Route::get('/articles/trending', [ArticleController::class, 'getTrending']);

Route::post('/articles/{article}/publish', [ArticleController::class, 'publish']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);

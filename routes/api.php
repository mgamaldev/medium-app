<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/articles', [ArticleController::class, 'store']);
    Route::post('/articles/{article}/comments', [CommentController::class, 'store']);

    Route::post('/users/{user}/follow', [UserController::class, 'store']);

});

Route::post('/articles/{article}/publish', [ArticleController::class, 'publish']);

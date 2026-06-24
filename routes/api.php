<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/articles', [ArticleController::class, 'store']);

});

Route::post('/articles/{article}/publish', [ArticleController::class, 'publish']);

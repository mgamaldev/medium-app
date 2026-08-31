<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-explain', function () {
    return DB::select("EXPLAIN SELECT count(*) FROM articles WHERE status = 'published' AND deleted_at IS NULL");
});

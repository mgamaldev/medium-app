<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\ArticleRepositoryInterface;
use App\Repositories\EloquentArticleRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

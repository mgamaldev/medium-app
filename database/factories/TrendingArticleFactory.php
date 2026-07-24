<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\TrendingArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrendingArticle>
 */
class TrendingArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'trending_score' => fake()->numberBetween(1, 100),
        ];
    }
}

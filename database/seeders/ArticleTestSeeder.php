<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Article::factory()->count(10)->create([
            'status' => ArticleStatus::DRAFT,
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);
        Article::factory()->count(5)->create([
            'status' => ArticleStatus::DRAFT,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        Article::factory()->count(5)->create([
            'status' => ArticleStatus::PUBLISHED,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        Article::factory()->count(5)->create([
            'status' => ArticleStatus::PUBLISHED,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
    }
}

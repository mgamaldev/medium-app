<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\TrendingArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_with_mass_assignable_attributes(): void
    {
        $article = Article::factory()->create();

        $trendingArticle = TrendingArticle::create([
            'article_id' => $article->id,
            'trending_score' => 150,
        ]);

        $this->assertDatabaseHas('trending_articles', [
            'id' => $trendingArticle->id,
            'article_id' => $article->id,
            'trending_score' => 150,
        ]);
    }

    public function test_only_article_id_and_trending_score_are_mass_assignable(): void
    {
        $article = Article::factory()->create();

        $trendingArticle = new TrendingArticle;
        $trendingArticle->fill([
            'article_id' => $article->id,
            'trending_score' => 200,
            'id' => 9999, // should be ignored — not in $fillable
        ]);

        $this->assertEquals($article->id, $trendingArticle->article_id);
        $this->assertEquals(200, $trendingArticle->trending_score);
        $this->assertNotEquals(9999, $trendingArticle->id);
    }

    public function test_it_belongs_to_an_article(): void
    {
        $article = Article::factory()->create();

        $trendingArticle = TrendingArticle::create([
            'article_id' => $article->id,
            'trending_score' => 75,
        ]);

        $this->assertInstanceOf(Article::class, $trendingArticle->article);
        $this->assertTrue($trendingArticle->article->is($article));
    }

    public function test_the_article_relationship_uses_the_correct_foreign_key(): void
    {
        $trendingArticle = new TrendingArticle;

        $relation = $trendingArticle->article();

        $this->assertSame('article_id', $relation->getForeignKeyName());
    }

    public function test_multiple_trending_articles_can_reference_different_articles(): void
    {
        $firstArticle = Article::factory()->create();
        $secondArticle = Article::factory()->create();

        TrendingArticle::create(['article_id' => $firstArticle->id, 'trending_score' => 100]);
        TrendingArticle::create(['article_id' => $secondArticle->id, 'trending_score' => 50]);

        $this->assertDatabaseCount('trending_articles', 2);
    }

    public function test_it_can_be_ordered_by_trending_score(): void
    {
        $lowScoreArticle = Article::factory()->create();
        $highScoreArticle = Article::factory()->create();

        TrendingArticle::create(['article_id' => $lowScoreArticle->id, 'trending_score' => 10]);
        TrendingArticle::create(['article_id' => $highScoreArticle->id, 'trending_score' => 999]);

        $ordered = TrendingArticle::orderBy('trending_score', 'desc')->get();

        $this->assertEquals($highScoreArticle->id, $ordered->first()->article_id);
        $this->assertEquals($lowScoreArticle->id, $ordered->last()->article_id);
    }

    public function test_deleting_the_related_article_does_not_automatically_delete_the_trending_record(): void
    {
        $article = Article::factory()->create();

        $trendingArticle = TrendingArticle::create([
            'article_id' => $article->id,
            'trending_score' => 30,
        ]);

        $article->delete();

        $this->assertDatabaseHas('trending_articles', [
            'id' => $trendingArticle->id,
        ]);

        $this->assertNull($trendingArticle->fresh()->article);
    }
}

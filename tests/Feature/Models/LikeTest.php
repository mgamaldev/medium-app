<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $like = Like::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        $relatedUser = $like->user;

        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
    }

    public function test_like_belongs_to_an_article()
    {
        $user = User::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        $like = Like::create([
            'user_id' => $user->id,
            'article_id' => $article1->id,
        ]);

        $relatedArticle = $like->article;

        $this->assertInstanceOf(Article::class, $relatedArticle);
        $this->assertEquals($article1->id, $relatedArticle->id);
        $this->assertNotEquals($article2->id, $relatedArticle->id);

    }
}

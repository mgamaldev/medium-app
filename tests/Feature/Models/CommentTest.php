<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'body' => 'Test Comment',
        ]);

        $relatedUser = $comment->user;

        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
    }

    public function test_comment_belongs_to_an_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'body' => 'Test Comment',
        ]);

        $relatedArticle = $comment->article;

        $this->assertInstanceOf(Article::class, $relatedArticle);
        $this->assertEquals($article->id, $relatedArticle->id);

    }
}

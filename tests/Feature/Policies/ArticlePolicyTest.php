<?php

namespace Tests\Feature\Policies;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_view_their_own_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => ArticleStatus::PUBLISHED,
            'visibility' => ArticleVisibility::PUBLIC,
        ]);

        $result = (new ArticlePolicy)->view($user, $article);

        $this->assertTrue($result);
    }

    #[Test]
    public function owner_can_update_their_own_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => ArticleStatus::PUBLISHED,
            'visibility' => ArticleVisibility::PUBLIC,
        ]);

        $result = (new ArticlePolicy)->update($user, $article);

        $this->assertTrue($result);
    }

    #[Test]
    public function owner_can_delete_their_own_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => ArticleStatus::PUBLISHED,
            'visibility' => ArticleVisibility::PUBLIC,
        ]);

        $result = (new ArticlePolicy)->delete($user, $article);

        $this->assertTrue($result);
    }

    #[Test]
    public function owner_can_restore_their_own_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => ArticleStatus::PUBLISHED,
            'visibility' => ArticleVisibility::PUBLIC,
        ]);

        $result = (new ArticlePolicy)->restore($user, $article);

        $this->assertTrue($result);
    }
}

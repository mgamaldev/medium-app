<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleVisibilityTest extends TestCase
{    
    use RefreshDatabase;
    
    protected ArticlePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->app->make(ArticlePolicy::class);

    }
    #[Test]
    public function guest_user_can_view_published_article()
    {
        $article = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);

        $result = $this->policy->view(null, $article);

        $this->assertTrue($result);
        
    }
    #[Test]
    public function authenticated_user_can_view_published_article()
    {
        $user = User::factory()->create();

        $article = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);

        $result = $this->policy->view($user, $article);

        $this->assertTrue($result);
    }
    #[Test]
    public function author_can_view_their_own_draft_article()
    {
        $author = User::factory()->create();

        $article = Article::factory()->create(['status' => ArticleStatus::DRAFT,'user_id' => $author->id]);

        $result = $this->policy->view($author,$article);

        $this->assertTrue($result);
    }
    #[Test]
    public function follower_can_view_followers_only_article()
    {
        $author = User::factory()->create();
        
        $follower = User::factory()->create();

        $article = Article::factory()->create(['status' => ArticleStatus::FOLLOWERS_ONLY,'user_id' => $author->id]);

        DB::table('user_follower')->insert([
            'user_id' => $author->id,
            'follower_id' => $follower->id,
            'created_at' => now(),
        ]);

        $result = $this->policy->view($follower,$article);

        $this->assertTrue($result);


    }
}

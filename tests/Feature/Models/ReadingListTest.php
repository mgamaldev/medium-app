<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_list_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $readingList = ReadingList::create([
            'user_id' => $user->id,
            'title' => 'Test Reading List',
        ]);

        $relatedUser = $readingList->user;
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);

    }

    public function test_reading_list_belongs_to_many_articles()
    {
        $user = User::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        $readingList = ReadingList::create([
            'user_id' => $user->id,
            'title' => 'Test Reading List',
        ]);
        $readingList->articles()->attach($article1);
        $readingList->articles()->attach($article2);

        $relatedArticles = $readingList->articles;

        $this->assertCount(2, $relatedArticles);
        $this->assertEquals($article1->id, $relatedArticles->first()->id);
        $this->assertEquals($article2->id, $relatedArticles->last()->id);
    }
}

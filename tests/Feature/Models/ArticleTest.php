<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Like;
use App\Models\ReadingList;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id]);

        $relatedUser = $article->user;

        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertEquals($user->id, $relatedUser->id);
    }

    public function test_article_belongs_to_many_tags()
    {
        $article = Article::factory()->create();

        $tag = Tag::create(['name' => 'Test Tag', 'slug' => 'test-tag']);
        $tag2 = Tag::create(['name' => 'Test Tag 2', 'slug' => 'test-tag-2']);

        $article->tags()->attach($tag);
        $article->tags()->attach($tag2);

        $relatedTags = $article->tags;

        $this->assertCount(2, $relatedTags);
        $this->assertEquals($tag->id, $relatedTags->first()->id);
        $this->assertEquals($tag2->id, $relatedTags->last()->id);
    }

    public function test_article_has_many_comments()
    {
        $article = Article::factory()->create();
        $user = User::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'body' => 'Test Comment',
        ]);
        $comment2 = Comment::create([
            'user_id' => $article->user_id,
            'article_id' => $article->id,
            'body' => 'Test Comment 2',
        ]);

        $relatedComments = $article->comments;

        $this->assertCount(2, $relatedComments);
        $this->assertEquals($comment->id, $relatedComments->first()->id);
        $this->assertEquals($comment2->id, $relatedComments->last()->id);
    }

    public function test_article_has_many_likes()
    {
        $article = Article::factory()->create();
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $like = Like::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        $like2 = Like::create([
            'user_id' => $user2->id,
            'article_id' => $article->id,
        ]);

        $relatedLikes = $article->likes;

        $this->assertCount(2, $relatedLikes);
        $this->assertEquals($like->id, $relatedLikes->first()->id);
        $this->assertEquals($like2->id, $relatedLikes->last()->id);
    }

    public function test_article_belongs_to_many_reading_lists()
    {
        $article = Article::factory()->create();
        $user = User::factory()->create();
        $readingList = ReadingList::create([
            'user_id' => $user->id,
            'title' => 'Test Reading List',
        ]);
        $readingList2 = ReadingList::create([
            'user_id' => $user->id,
            'title' => 'Test Reading List 2',
        ]);

        $readingList->articles()->attach($article);
        $readingList2->articles()->attach($article);

        $relatedReadingLists = $article->readingLists;

        $this->assertCount(2, $relatedReadingLists);
        $this->assertEquals($readingList->id, $relatedReadingLists->first()->id);
        $this->assertEquals($readingList2->id, $relatedReadingLists->last()->id);
    }
}

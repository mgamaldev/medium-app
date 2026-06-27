<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_articles()
    {
        $tag = Tag::create([
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        $tag->articles()->attach($article1);
        $tag->articles()->attach($article2);

        $relatedArticles = $tag->articles;

        $this->assertCount(2, $relatedArticles);
        $this->assertEquals($article1->id, $relatedArticles->first()->id);
        $this->assertEquals($article2->id, $relatedArticles->last()->id);
    }
}

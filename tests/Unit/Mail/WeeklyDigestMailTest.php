<?php

namespace Tests\Unit\Mail;

use App\Mail\WeeklyDigestMail;
use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklyDigestMailTest extends TestCase
{
    public function test_it_has_the_correct_subject(): void
    {
        $mailable = new WeeklyDigestMail(new Collection);

        $mailable->assertHasSubject('Your Weekly Digest Mail');
    }

    public function test_it_uses_the_correct_view(): void
    {
        $mailable = new WeeklyDigestMail(new Collection);

        $content = $mailable->content();

        $this->assertSame('emails.weekly-digest', $content->view);
    }

    public function test_it_exposes_the_articles_collection_passed_to_it(): void
    {
        $articles = Collection::make([
            new Article(['title' => 'First Article']),
            new Article(['title' => 'Second Article']),
        ]);

        $mailable = new WeeklyDigestMail($articles);

        $this->assertSame($articles, $mailable->articles);
        $this->assertCount(2, $mailable->articles);
    }

    public function test_it_has_no_attachments(): void
    {
        $mailable = new WeeklyDigestMail(new Collection);

        $this->assertSame([], $mailable->attachments());
    }

    public function test_it_handles_an_empty_articles_collection_without_errors(): void
    {
        $mailable = new WeeklyDigestMail(new Collection);

        $this->assertCount(0, $mailable->articles);
        $mailable->assertHasSubject('Your Weekly Digest Mail');
    }

    public function test_mail_is_sent_with_the_expected_mailable_when_dispatched(): void
    {
        Mail::fake();

        $recipientEmail = 'subscriber@example.com';
        $articles = Collection::make([new Article(['title' => 'Trending Article'])]);

        Mail::to($recipientEmail)->send(new WeeklyDigestMail($articles));

        Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($recipientEmail, $articles) {
            return $mail->hasTo($recipientEmail)
                && $mail->articles->count() === $articles->count();
        });
    }

    public function test_mail_is_not_sent_to_unintended_recipients(): void
    {
        Mail::fake();

        Mail::to('subscriber@example.com')->send(new WeeklyDigestMail(new Collection));

        Mail::assertNotSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) {
            return $mail->hasTo('someone-else@example.com');
        });
    }

    public function test_mailable_renders_the_view_with_articles_data(): void
    {
        $articles = Collection::make([
            new Article(['title' => 'A Rendered Article Title']),
        ]);

        $mailable = new WeeklyDigestMail($articles);

        $mailable->assertSeeInHtml('A Rendered Article Title');
    }
}

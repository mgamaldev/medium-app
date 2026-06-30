<h1>Your Weekly Digest Mail</h1>
<p>Here are some articles you might like:</p>
<ul>
    @foreach($articles as $article)
        <li>{{ $article->title }}</li>
    @endforeach
</ul>
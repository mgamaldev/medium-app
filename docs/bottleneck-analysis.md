1-Memory & N+1 Issue:
    The problem: The Article::all() method fetches all records from the database into memory without eager-loading or adding pagination to limit the amout of the upcoming data.

    The scale trigger: When the articles table grows to thousands of records, this will cause a memory leak error and slow down the app.

    The mitigation: Replace Article::all() with Pagination and use Eager Loading.

2-missing database indexes
    The problem: Tables like follows, likes and articles are queried frequently using WHERE clauses but lack proper database indexes in their migration files.

    The scale trigger: As the data volume grows to tens of thousands of rows, database queries like checking if a user follows an author, or fetching an article by its slug, will trigger expensive Full Table Scans instead of quick index lookups.

    The mitigation: Create and run a new database migration to add explicit indexes and composite indexes to these columns.

3-Un-cached hot-read endpoints
        The problem: The getTrending() method in EloquentArticleRepository queries the database directly every time it's called, even though trending articles are requested by almost every visitor on the homepage.

        The scale trigger: Under high concurrent traffic like more than 1000 users per minute, this will cause database CPU exhaustion and crash the database.

        The mitigation: mplement Caching like Redis or Laravel Cache inside the repository or controller to cache the trending articles for a limited time depending on the website needs instead of querying the DB on every request.

4-Files stored on local disk
        The problem: The SendAuthorNotification listener handles notifications synchronously within the HTTP request cycle when an article is created.

        The scale trigger: As notification logic expands (e.g., sending emails or push notifications to many users), the response time for the author creating the article will skyrocket, leading to a bad user experience or request timeouts.

        The mitigation: Implement the ShouldQueue interface on the SendAuthorNotification listener so that notifications are processed asynchronously in the background.
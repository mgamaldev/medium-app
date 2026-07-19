# Contributing

Thanks for helping improve this project. This guide covers the branching
model, the checks to run before opening a PR, what CI enforces, and the
commit message convention we use. Following it means your PR is more likely
to pass CI on the first try and get reviewed quickly.

## Branching model

## 1. Branching Model

We maintain a clean and focused repository history:
*   **Base Branch:** Always create your branch from the latest `main` branch.
*   **One Task, One PR:** Each PR must address a single, specific issue or feature.
*   **No Stacked Branches:** Do not base your branch on another unmerged Pull Request. 

### Recommended Workflow:
```bash
git checkout main
git pull origin main
git checkout -b feature/your-task-name
```

## Before opening a PR

Run the full local checklist and make sure everything is green:

```bash
vendor/bin/pint          # fix code style automatically
vendor/bin/pint --test   # verify style with no changes (what CI runs)
vendor/bin/phpstan analyse
php artisan test
```

A PR should only be opened once all three of these pass locally:

- [ ] `vendor/bin/pint --test` — no style violations
- [ ] `vendor/bin/phpstan analyse` — no static analysis errors
- [ ] `php artisan test` — full test suite passes

This mirrors exactly what CI runs, so a clean local run means there are no
surprises after you push.

## What CI enforces

Every push and pull request against `main` triggers three jobs, all of which
must pass:

| Job | Command | Checks |
|---|---|---|
| Code Style (Pint) | `vendor/bin/pint --test` | Code formatting |
| Static Analysis (PHPStan) | `vendor/bin/phpstan analyse` | Type safety, static analysis |
| Tests (PHPUnit) | `vendor/bin/phpunit` | Full test suite (SQLite in-memory DB) |

**All three checks must pass before a PR can be merged.** CI is configured
as a required status check on `main`, so GitHub will block merging until
Code Style, Static Analysis, and Tests all report success — there is no
manual override. If a job fails, push a fix to the same branch; CI will
re-run automatically.

## Commit message convention

Write commits so the history stays readable and searchable. Follow
[Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <short summary>

```

- **type** — one of: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`
- **scope** — optional, the area of the codebase affected (e.g. `auth`, `api`, `ci`)
- **summary** — imperative mood, lowercase, no trailing period (e.g. `add`, not `added` or `adds`)

Examples:

```
feat(auth): add password reset endpoint
fix(orders): correct tax calculation for bulk discounts
docs: update CONTRIBUTING with commit conventions
test(orders): cover edge case for zero-quantity items
```

Keep the summary under ~72 characters. Use the body to explain *why* a
change was made if it isn't obvious from the diff.

## Opening the PR

- Push your branch and open a PR against `main`.
- Give the PR a title that follows the same commit convention above.
- Fill in a short description of what changed and why.
- Wait for CI to go green before requesting review — a PR with failing
  checks won't be merged regardless of review status.
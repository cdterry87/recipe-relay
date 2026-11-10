# Recipe Relay

A friendly place to keep your favorite recipes and relay the good ones to friends, family and followers. Built with Laravel, Livewire, Alpine and hand-written CSS (no Tailwind or UI kits). See [docs/spec.md](docs/spec.md) for the full specification.

The original Recipeek codebase is archived on the `archive-2025` branch.

## What it does

- **Recipes**:
  - Each recipe has ingredients (with a servings scaler), steps, a photo, an optional video, collections and dietary tags.
  - Each recipe is private, unlisted (anyone with the link) or public.
  - Recipe pages have cook mode (larger text, and the screen stays on) and a print layout.
- **Discover**: search, filters (meal, cuisine, method, difficulty, time, rating, collections, diets), sorting, collection pages, and "Surprise me".
- **Social**:
  - Save recipes to a cookbook, then rate them.
  - Follow creators.
  - A following feed, creator profiles and in-app notifications.
- **Relay**:
  - A share sheet (copy link, email, social networks) and "Email a friend".
  - A weekly digest at each member's chosen day and hour.
  - Email preferences and one-click unsubscribe.
- **Accounts**:
  - Email and password or Google sign-in, with email verification and password resets.
  - Profile photos, light and dark themes.
- **Demo mode** for the portfolio site:
  - A one-click demo login protected by Turnstile.
  - Seeded sample creators and recipes, restored every night.
  - The demo account can't upload, email or publish.

## Local development

Requires Docker. Sail runs PHP 8.4, MySQL and Mailpit.

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install
npm run dev
```

`--seed` builds the sample creators, the 21 seed recipes and the demo account, the same as `demo:reset`.

- App: http://localhost:8003. With `DEMO_MODE=true`, use "Log in as the demo user" on the login page.
- Mailpit: http://localhost:8027
- Style guide of shared components: http://localhost:8003/styleguide
- Queued mail and notifications: `./vendor/bin/sail artisan queue:work`

## Commands

| Command | What it does |
|---|---|
| `php artisan user:create` | Creates a verified account, asking for the password. Use it when registration is closed in demo mode. |
| `php artisan demo:reset` | Creates or restores the sample creators, seed recipes and demo account. Runs nightly in demo mode. |
| `php artisan digest:send` | Queues the weekly digests that are due this hour. Runs hourly. |

## Checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

`./test-coverage-report.sh` writes an HTML coverage report to `tests/reports/coverage`. CI runs Pint, Larastan and the test suite on every pull request.

## Deployment

See [docs/deployment.md](docs/deployment.md) for Laravel Forge: environment, deploy script, queue worker, scheduler and the first-run commands.

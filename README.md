# Recipe Relay

A friendly place to keep your favorite recipes and relay the good ones to friends, family and followers. Built with Laravel, Livewire, Alpine and hand-written CSS (no Tailwind or UI kits). See [docs/spec.md](docs/spec.md) for the full specification.

The original Recipeek codebase is archived on the `archive-2025` branch.

## Local development

Requires Docker. Sail runs PHP 8.4, MySQL and Mailpit.

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

- App: http://localhost:8003
- Mailpit: http://localhost:8027
- Style guide of shared components: http://localhost:8003/styleguide

## Checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

`./test-coverage-report.sh` writes an HTML coverage report to `tests/reports/coverage`.

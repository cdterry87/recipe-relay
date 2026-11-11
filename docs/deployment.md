# Deploying Recipe Relay with Laravel Forge

This guide puts Recipe Relay on a Forge-managed server as the portfolio demo. It assumes one app server with MySQL on the same machine, which is plenty for a demo. Section B11 of [spec.md](spec.md) explains what demo mode does.

## 1. Server

Create a server in Forge (any provider) with:

- **PHP 8.4** (the app requires `^8.4`; `composer.json` pins the platform to 8.4).
- **MySQL 8.x**. Create a database and a user for the site. MariaDB is not tested.
- **Node 20 or newer**, for the Vite build during deploys. Forge installs Node on new servers.
- The GD PHP extension (installed by default). Intervention Image uses it to make WebP photos.
- **PHP limits** (Server → PHP → Max File Upload Size and the FPM php.ini):
  - `upload_max_filesize` and `post_max_size` of at least 10M, since photos may be up to 8 MB.
  - `memory_limit` of at least 512M. Photos are capped at 50 megapixels, and decoding one takes about 4 bytes per pixel.

## 2. Site

1. **New site**: the domain, project type "Laravel", web directory `/public`, PHP 8.4.
2. **Repository**: `cdterry87/recipe-relay`, branch `main`. Turn on **Quick Deploy** if you want every merge to `main` to deploy.
3. **SSL**: issue a Let's Encrypt certificate (or use Cloudflare's), then turn on HTTPS redirects.

## 3. Environment

In **Site → Environment**, start from `.env.example` and change at least these values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://recipes.example.com   # https: also marks the session cookie Secure
APP_KEY=                              # Forge generates one; otherwise run `php artisan key:generate --show`
LOG_LEVEL=warning

DB_HOST=127.0.0.1
DB_DATABASE=recipe_relay
DB_USERNAME=forge
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local                 # uploads go to the "public" disk, served through storage:link

# A transactional mail provider (Resend, Postmark, SES...). The From address must be on a
# domain the provider has verified.
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hello@recipes.example.com"

# Real Cloudflare Turnstile keys. The test keys are rejected in production, which turns every
# Turnstile check into a failure (fail closed).
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...

# Optional: Google sign-in. Leave both empty to hide the button.
# Authorised redirect URI in Google Cloud: https://recipes.example.com/auth/google/callback
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# Only if traffic reaches the server through a proxy or CDN such as Cloudflare. Rate limits
# key on the client IP, so without this every visitor looks like the proxy.
TRUSTED_PROXIES=

# Portfolio demo
DEMO_MODE=true
DEMO_NAME="Demo User"
DEMO_EMAIL=demo@example.com
DEMO_PASSWORD=...                     # any value; visitors never see or type it
DEMO_TIMEZONE="America/Chicago"       # the nightly reset runs at midnight here
```

**Trusted proxies with Cloudflare.** When Cloudflare proxies the site, set `TRUSTED_PROXIES` to Cloudflare's IP ranges (https://www.cloudflare.com/ips/), comma-separated. If the firewall only allows traffic from Cloudflare, `*` is acceptable.

## 4. Deploy script

Replace Forge's default deploy script with:

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
$FORGE_PHP artisan queue:restart
```

`artisan optimize` caches the config, routes, views and events. After changing environment variables, redeploy (or run `php artisan optimize`) so the cached config picks them up.

## 5. First deploy

Run these once, from **Site → Commands** or over SSH, after the first successful deploy:

```bash
php artisan storage:link   # serves uploaded recipe photos and avatars from /storage
php artisan demo:reset     # creates the sample creators, seed recipes and demo account
```

`demo:reset` refuses to run if a regular account already uses `DEMO_EMAIL` or one of the sample addresses (`*@samples.invalid`). Add a real account for yourself with:

```bash
php artisan user:create
```

## 6. Queue worker

Emails (verification, password resets, digests, "email a friend") and notifications are queued. In **Site → Queue**, add a worker:

| Setting | Value |
|---|---|
| Connection | `database` |
| Queue | `default` |
| Maximum seconds per job | `60` |
| Sleep when no job | `3` |
| Maximum tries | `3` |
| Processes | `1` |

The deploy script's `queue:restart` makes the worker pick up new code.

## 7. Scheduler

In **Server → Scheduler**, add a job that runs **every minute**:

```bash
php /home/forge/recipes.example.com/current/artisan schedule:run
```

(Use the site's real path. Sites without zero-downtime deploys have no `/current`.) The schedule lives in `routes/console.php`:

| Command | When | Purpose |
|---|---|---|
| `digest:send` | hourly | Queues weekly digests that are due in each member's timezone |
| `demo:reset` | daily at 00:00 in `DEMO_TIMEZONE` | Restores the demo world (only when `DEMO_MODE=true`) |
| `auth:clear-resets` | hourly | Removes expired password reset tokens |

Check it with `php artisan schedule:list`.

## 8. After deploying

- Visit `/up` (Laravel's health check). It should return 200.
- Log in with the demo button, then confirm the home page shows the seed recipes and photos.
- Send yourself a password reset or an "email a friend" message to confirm the mail provider and the queue worker.
- In the browser's developer tools, check that the response headers include `Content-Security-Policy`, `X-Frame-Options: DENY` and `Strict-Transport-Security`.
- Optional: Forge's **Monitoring** for disk and CPU, and **Database Backups** to object storage. The demo data rebuilds itself, so backups matter only for real accounts.

## Hardening the web server

Uploads are stored only as re-encoded `.webp` files with random names. Never-trusted temporary uploads stay in `storage/app/livewire-tmp`, outside the web root. As a second line of defence, stop nginx from ever running PHP under `/storage`. Add this to the site's nginx config, above the main `location ~ \.php$` block:

```nginx
location ~* ^/storage/.*\.php$ {
    deny all;
}
```

## Caching and static files

Forge's default nginx config already serves `/build/*` (hashed Vite assets), `/images/seed/*` and `/storage/*` as static files. For long-lived browser caching of the hashed build files, add this to the site's nginx config:

```nginx
location /build/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

The seed photos and uploads aren't hashed, so leave them on nginx's default caching.

## Turning demo mode off

Set `DEMO_MODE=false` and redeploy. Registration (protected by Turnstile) opens, the demo login disappears, and the nightly reset stops. The sample creators and seed recipes remain until you delete them. The demo account can still log in with its password, so delete it as well, or change `DEMO_PASSWORD` and run `demo:reset` one last time.

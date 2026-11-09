# Recipe Relay — Product & Technical Specification

> Status: **Draft v0.2** — 2026-11-09 (open questions answered; demo mode added)
> Supersedes: *Recipeek* (archived on branch `archive-2025`)

---

## 1. Purpose of this document

This spec does two jobs:

1. **Records what the legacy project (Recipeek) actually does** — its stack, data model, features, and known problems — so nothing valuable is lost when `main` is cleared.
2. **Defines what Recipe Relay will be** — a ground-up rebuild on the latest Laravel, Livewire and Alpine, with a fully custom design system (no Tailwind, no UI kits).

Recipe Relay keeps Recipeek's premise but does not need to match it feature-for-feature. Where the two differ, Part B wins.

---

## 2. Rebuild plan (branching)

| Step | Action |
|------|--------|
| 1 | Write this spec in `docs/spec.md` on `main`. |
| 2 | Create `archive-2025` from the current `main` HEAD (`79aa465`) and push it. Treat it as read-only. |
| 3 | On `main`, remove everything except `docs/`. |
| 4 | Scaffold a new Laravel app on `main` (latest stable), keeping `docs/`. |
| 5 | Build Recipe Relay in milestones (see §B15), with each milestone landing through a PR. |

Notes:
- The legacy site is not running and has no user data to migrate. Recipe Relay is a fresh start.
- The legacy `production` branch, `.github/workflows/main.yml`, `deploy.sh` and `server_deploy.sh` stay in `archive-2025` only. They are not carried over.
- Useful assets worth carrying over from the archive: `database/data/recipes.json` (21 seed recipes) and the matching `public/demo/*.jpg` images, the favicon set, and the enum vocabularies in §A4.

---

# Part A — Legacy analysis: Recipeek

## A1. Summary

Recipeek ("Take a Peek, Then Eat!") is a Laravel 11 + Livewire 3 recipe-sharing app built between February and April 2025 (about 50 commits). Users register (email/password or Google), create recipes with ingredients and step-by-step instructions, mark them public or private, save other people's recipes, rate them 1–5 stars, follow creators, add friends, and share recipes to social networks or by email.

## A2. Stack

| Layer | Legacy choice |
|-------|---------------|
| PHP / framework | PHP ^8.2, Laravel ^11.31 |
| Reactive UI | Livewire ^3.5 (full-page components for every screen), Alpine (bundled with Livewire) |
| Styling | Tailwind CSS 4 + daisyUI 5 (custom light/dark themes in `resources/css/app.css`) |
| Packages | `laravel/socialite` (Google login), `spatie/laravel-sluggable` (slugs), `barryvdh/laravel-dompdf` (recipe PDF) |
| Database | MySQL 8 (Sail); SQLite option in `.env.example` |
| Local env | Laravel Sail (`docker-compose.yml`): MySQL, Redis, Meilisearch, Mailpit, Selenium. Only MySQL and Mailpit are relevant to the app. |
| Testing | PHPUnit 11, 21 feature test files (~65 tests), HTML coverage script |
| Deploy | `deploy.sh` runs the tests, then merges `main` into `production`. A GitHub Action on `production` builds assets, force-pushes a `deploy` branch, and SSHes in to run `server_deploy.sh` at `/var/www/recipeek`. |

## A3. Data model (as built)

```
users
  id, name, email (unique), bio, avatar, email_verified_at, password,
  public (bool, default false), slug (unique), private_friend_request_id (uuid, unique),
  remember_token, timestamps

recipes
  id, uuid (unique), user_id → users, title (60), slug (unique), description (255),
  category, cuisine, difficulty, method, occasion (string(20) enum values),
  hours, minutes, servings, calories, image (path), video (raw embed HTML), notes,
  public (bool, default false), timestamps

recipes_ingredients   id, recipe_id, ingredient (60), quantity (20), unit (20), timestamps
recipes_instructions  id, recipe_id, instruction (text), order (int), timestamps
recipes_tags          id, recipe_id, tag (string), timestamps          ← no UI uses this
recipes_saves         id, recipe_id, user_id, timestamps              ← no unique constraint
recipes_ratings       id, recipe_id, user_id, rating (1–5), unique(user_id, recipe_id)
users_followers       id, user_id (followed), follower_id, unique pair
users_friends         id, user_id, friend_id, accepted_at, unique pair (two rows per friendship)
users_friend_requests id, from_user_id, to_user_id, timestamps
contact_messages      id, name, email, message
subscribers           id, email (unique), token, confirmed_at        ← newsletter, never confirmed or mailed
password_reset_tokens (custom use — see A6)
```

## A4. Taxonomy (PHP backed enums in `app/Enums`)

| Enum | Values |
|------|--------|
| Category | Breakfast, Lunch, Dinner, Snack, Dessert, Appetizer, Side, Soup, Salad, Beverage, Smoothie |
| Cuisine | African, American, Australian, British, Caribbean, Chinese, French, German, Indian, Italian, Japanese, Korean, Latin American, Mediterranean, Mexican, Middle Eastern, Southern, Thai, Vietnamese |
| Difficulty | Easy, Normal, Hard, Expert |
| Method | Air Fryer, Assembled, Baked, BBQ, Boiled, Broiled, Deep Fried, Grilled, Instant Pot, Raw, Roasted, Sautéed, Simmer, Slow Cooker, Smoked, Steamed, Stir-Fried, Stovetop |
| Occasion | Any Occasion, Birthday, Everyday, Holiday, Christmas, Thanksgiving, Easter, Summer, Winter |
| Dietary tag (unused) | Vegan, Vegetarian, Gluten-Free, Dairy-Free, Nut-Free, Soy-Free, Keto, Paleo, Low-Carb, High-Protein, Sugar-Free, Halal, Kosher |
| Time bucket (derived) | Fast (≤30 min), Normal (31–60 min), Time-Intensive (>60 min) |
| Sort | Title, Date, Popularity (save count), Rating (average), Cook time; asc/desc; 12/24/48/72/96 per page |

## A5. Pages & features

| Route | Component | Auth | What it does |
|-------|-----------|------|--------------|
| `/` | `Welcome` | — | Background video hero, search box (redirects to Discover), 4 random public recipes, hard-coded "featured creator" (`demo@example.com`), newsletter signup |
| `/login` | `LoginRegister` | guest | Combined login/register, plus a "Continue with Google" button |
| `/forgot-password`, `/reset-password/{token}` | `ForgotPassword`, `ResetPassword` | guest | Custom reset-token flow with a 2-hour expiry |
| `/discover` | `DiscoverRecipes` | — | Search (3+ chars, title LIKE), filters (category, cuisine, difficulty, method, occasion, time), sort, pagination |
| `/recipes/{slug}` | `ViewRecipe` | — | Recipe detail: metadata, ingredients, ordered steps, video embed, notes, save/unsave, edit (owner), rating, share modal, PDF download. Shows a "private recipe" placeholder to non-owners. |
| `/creators/{slug}` | `ViewCreator` | — | Creator profile and their public recipes (filterable). Follow/unfollow, send/cancel friend request. Shows a "private creator" placeholder unless the profile is public or the viewer is a friend. |
| `/profile` | `UserProfile` | auth | Edit name, email, bio, avatar, public flag. Change password. Regenerate or clear the private friend-request link. |
| `/my-recipes` | `MyRecipes` | auth | The user's own recipes, with the same filters and sort |
| `/create-recipe` | `CreateRecipe` | auth | Recipe metadata form, then redirects to edit to add ingredients and steps |
| `/edit-recipe/{uuid}` | `EditRecipe` + `RecipeIngredientsForm` + `RecipeInstructionsForm` | auth | Edit metadata, image, delete recipe. Add/remove ingredients. Add/remove steps (insert at a position shifts the others). |
| `/saved-recipes` | `SavedRecipes` | auth | Saved recipes with filters, plus a "random recipe" button |
| `/following` | `Following` | auth | List of followed creators: search, unfollow, view |
| `/friends`, `/friend-requests` | `Friends`, `FriendRequests` | auth | Friends list; received and sent requests (accept, reject, cancel) |
| `/private-friend-request/{id}` | `PrivateFriendRequest` | auth | Shareable invite link that sends a friend request to a private user |
| `/about` (+ `ContactForm`) | Blade | — | About page and a contact form, stored in the DB only |
| `/privacy-policy`, `/terms-of-use`, `/cookie-policy` | Blade | — | Legal pages |
| `/auth/google/*` | `GoogleController` | — | Socialite OAuth |

**Sharing** (share modal): Facebook, X/Twitter, Pinterest, WhatsApp, `mailto:`, and copy link.
**Print**: a server-side PDF built with dompdf.
**Errors**: custom 404, 500 and 503 pages with illustrations.

## A6. Known problems in the legacy code

These are lessons for the rebuild, not bugs to fix in the archive.

**Security / privacy**
- **Discover lists private recipes.** `DiscoverRecipes` never filters `public = true`, so private recipes appear in results. The detail page hides them, but titles and images still leak.
- **Stored XSS.** The recipe `video` field stores raw HTML and renders it with `{!! $recipe->video !!}`.
- **Unscoped deletes.** `RecipeIngredientsForm::removeIngredient` and `RecipeInstructionsForm::removeInstruction` delete by ID without checking ownership, and the form `recipeId` is client-mutable. Any logged-in user could delete another user's ingredients and steps.
- **Mass assignment.** `Recipe` and its child models use `$guarded = []`.
- The `User` model `$fillable` includes `private`, but the column is called `public`.
- Profile email changes skip uniqueness checks and re-verification.
- Password minimum is 6 characters at registration but 8 on reset.

**Broken or incomplete features**
- **No email is ever sent.** Forgot-password creates a token but sends nothing. Newsletter subscribers are never confirmed or mailed. Contact messages are only stored. No notifications exist (follows, new recipes, digests).
- Dietary tags have a table and an enum, but no UI.
- `SavedRecipes::randomRecipe` redirects to a non-existent `recipes.index` route.
- `ViewRecipe` builds its title from `$recipe->name`, which does not exist (should be `title`).
- `EditRecipe::removeImage` deletes `storage/...` paths from the `public` disk, which leaves orphaned files.
- Users can rate without having saved or cooked a recipe. Rating `0` passes validation.
- "Featured creator" is hard-coded to the demo account.

**Code quality**
- The filter, sort and time-bucket query (~70 lines) is copy-pasted into four components. It also interpolates `sort_by` and `sort_direction` into `orderBy` and `orderByRaw` without allow-listing.
- No policies or gates. Authorization is inline and inconsistent.
- No eager loading on cards (N+1 queries for ratings, saves and users).
- Each friendship is stored as two rows; the friend and follow concepts overlap.
- Text search is `LIKE '%term%'` on the title only.
- Default Laravel README; no project docs.

---

# Part B — Recipe Relay specification

## B1. Vision

**Recipe Relay** is a friendly place to keep your own recipes and pass the good ones along. The name is the product: you cook something, love it, and *relay* it to friends, family and followers.

Design pillars:
1. **Fun and friendly for all ages.** Big, readable type; warm colors; playful micro-interactions; plain language ("Yum! Saved to your cookbook").
2. **Sleek and modern.** Generous whitespace, clean cards, smooth transitions, and a fast feel with no page reloads where Livewire helps.
3. **Accessible by default.** Meets WCAG 2.2 AA, works with a keyboard and a screen reader, respects reduced motion, and has large tap targets.
4. **Simple.** Every feature must justify itself. Fewer, better screens.

**Context:** Recipe Relay is a portfolio application. It is built to production quality, but it is not run as a public service. It normally runs in **demo mode** (§B11), where public registration is closed, visitors try it through a demo account, and the owner adds real accounts with `php artisan user:create` (§B12).

## B2. Users

| Kind | Can do |
|------|--------|
| **Guest** | Browse and search public recipes, open unlisted recipes from a share link, view creator profiles, share public recipes. Sign up when registration is open. |
| **Member** | Everything a guest can, plus: create, edit and delete their own recipes; set each recipe's visibility; save recipes to their cookbook; rate recipes they have saved; follow creators; manage their profile and email preferences. |
| **Demo user** | A member account with the restrictions in §B11.3. Restored every night. |
| **Sample creator** | Seeded accounts that own most of the launch recipes. They cannot log in (no password) and never receive email. |

There is no admin role or admin area in v1.

## B3. Core features (v1)

### B3.1 Accounts
- **Registration** (only when demo mode is off): name, email, password. Protected by Cloudflare Turnstile (§B13) and rate limiting. Logs the user in on success.
- **Google sign-in** (Socialite), the only social login. When registration is open it can create an account. When registration is closed it signs in existing accounts only (matched on a verified Google email), and otherwise shows "Registration is closed".
- **Email verification** is required before a member can publish a recipe as public or unlisted. Accounts made with `user:create` or Google are verified already.
- **Login** with "remember me", logout, and forgot/reset password through Laravel's `Password` broker. Real emails are sent. Forgot-password always shows the same neutral message.
- **Profile:** display name, avatar upload (cropped to a square), short bio (≤ 280 characters), timezone.
- **Account settings:** change email (re-verify), change password, email preferences (§B3.7), delete account (current password required; deletes their recipes, saves, ratings and follows).
- **Password rules:** one `Password::defaults()` definition used everywhere, minimum 8 characters.
- Rate limiting on login, registration, password reset and the demo button.

### B3.2 Recipes
Fields:
- **Required:** title (≤ 80), at least one ingredient, at least one step, visibility.
- **Optional:** summary (≤ 280), hero image, prep time, cook time (total is derived), servings, difficulty, meal type, cuisine, cooking method, collections (§B3.3), dietary tags, occasion, calories, tips/notes, video URL.
- **Ingredients:** an ordered list of `{quantity, unit, name, note}`. Add, edit, remove and reorder them (drag, or keyboard up/down). Optional section headings such as "For the sauce".
- **Steps:** an ordered list of short text blocks. Plain text with line breaks, rendered escaped. Add, edit, remove and reorder.
- **Video:** store a URL, not HTML. Embed only allow-listed providers (YouTube, Vimeo) using an embed URL we build ourselves.
- **Drafts:** a recipe can be saved incomplete. Only complete recipes can be public or unlisted.
- **One-page editor:** a single create/edit screen with sections (Basics → Ingredients → Steps → Extras). Uses Livewire Form objects.
- **URLs:** `/recipes/{uuid}`. Recipes have no slug, so renaming a recipe never changes its URL (§B5.1).
- Delete with a confirmation dialog.
- A limit of 200 recipes per account stops a script from filling the database (25 for the demo user).

#### Visibility
| Level | Who can open it | Where it is listed |
|-------|-----------------|--------------------|
| **Private** (default) | Owner only. Everyone else gets a 404, so its existence is not revealed. | Owner's *My Recipes* only |
| **Unlisted** | Anyone with the share link | Nowhere: not in Discover, search, profiles, feeds, digests, the sitemap or "more like this". Served with `noindex`. |
| **Public** | Everyone | Everywhere |

- An **unlisted recipe is only reachable through its share link** `/r/{token}`, where the token is random (32 characters). `/recipes/{uuid}` returns 404 to non-owners for unlisted recipes. A separate token is used instead of the UUID because the owner can reset it, which breaks the old link, while a UUID never changes (and an ordered UUID reveals when the record was created).
- Only public and unlisted recipes can be saved, rated or shared by others.
- If a recipe is made private, people who saved it see a "No longer available" card in their cookbook and can remove it. Their saves and ratings are kept, in case it becomes visible again.

### B3.3 Discover, search & filter
- The **Discover** page lists **public recipes only**, as a card grid with "Load more" and URL-synced filters (`#[Url]`), so filtered views are shareable.
- **Search:** keyword across title, summary, ingredient names and creator name, using MySQL `FULLTEXT`.
- **Filters** (combinable, shown as friendly chips):
  - **Meal type** (one per recipe): Breakfast, Brunch, Lunch, Dinner, Snack, Appetizer, Side, Soup, Salad, Dessert, Drink.
  - **Collections** (any number per recipe): a fixed list — Quick & Easy, Slow Cook, One-Pot, Kid-Friendly, Make-Ahead, Budget-Friendly, Crowd-Pleaser. The creator ticks checkboxes in the editor. Some boxes are pre-ticked by a rule the first time the recipe's details make it fit, and the creator can untick them:
    - **Quick & Easy:** total time ≤ 30 minutes and difficulty Easy.
    - **Slow Cook:** method Slow Cooker or Smoked, or total time ≥ 4 hours.
    - Other collections have no rule; they are ticked by hand only.
  - **Cuisine, Method, Difficulty, Dietary tags** (multi-select; dietary tags match with AND).
  - **Total time:** ≤ 15, ≤ 30, ≤ 60, or 60+ minutes.
  - **Star rating:** 4★ & up, 3★ & up, 2★ & up (cached average; unrated recipes are excluded when this filter is set).
- **Sort:** Newest, Top rated, Most saved, Quickest, A–Z. Allow-listed through an enum.
- The same filter component is reused on **My Recipes**, **Cookbook** and **Creator profile**, each with its own base query.
- **Collection pages:** `/collections/{collection}` shows public recipes in one collection, for example `/collections/quick-and-easy`.
- **"Surprise me"** button opens a random recipe from the current results.

### B3.4 Recipe page
- Hero image, title, creator chip (avatar, name, follow button), rating summary, and quick facts (time, servings, difficulty, meal type), plus collection and dietary chips that link to filtered Discover.
- Ingredients with a **servings scaler** (Alpine; scales numeric quantities) and tap-to-check-off.
- Steps with tap-to-mark-done, and a **Cook Mode** that keeps the screen awake (Wake Lock API) and shows larger text.
- Save to cookbook, rate (once saved), share, print (a print stylesheet), and Edit (owner only).
- A "More from this creator" strip and a "You might also like" strip (same meal type or cuisine). Public recipes only.
- **SEO** for public recipes: schema.org `Recipe` JSON-LD, Open Graph and Twitter card meta, and a canonical URL.

### B3.5 Ratings
- **A member can rate a recipe only after saving it.** Owners cannot save their own recipes, so they cannot rate them.
- One rating per member per recipe, 1–5 stars. They can change it.
- **Unsaving a recipe deletes that member's rating** in the same transaction. The UI warns about this first: "Removing this recipe from your cookbook also removes your 4★ rating."
- Stars only. No comments, and no "I made this" button.
- Each recipe stores a cached `rating_avg` and `rating_count`, recalculated whenever a rating is added, changed or removed.
- Display: average to one decimal, plus the count ("4.6 ★ · 23 ratings"). Show "New" when there are fewer than 3 ratings.
- The stars are shown read-only with a "Save to rate" hint until the member saves the recipe.

### B3.6 Cookbook (saved recipes)
- Save or unsave any public or unlisted recipe you don't own, with a heart toggle.
- A guest who clicks Save is sent to login, then returned to the recipe and the save completes.
- The Cookbook page uses the shared filter component, plus "Surprise me" from saved recipes.

### B3.7 Following & notifications
- Follow or unfollow any creator who has at least one public recipe. Following is one-way and needs no approval.
- **Following feed:** newest public recipes from followed creators.
- **Weekly digest email** (the core notification):
  - Each member picks a day and hour (default **Sunday 09:00**) in their own timezone. An hourly scheduled command finds the members due now and queues a Markdown mailable for each.
  - Contents: for each followed creator who published a public recipe in the past 7 days, their name and avatar plus up to 3 of those recipes. Then a "Trending this week" section with a few top-rated new public recipes from the community.
  - Skipped when there is nothing new. Never includes private or unlisted recipes.
  - One-click unsubscribe with a signed URL and `List-Unsubscribe` headers.
  - **Digest preview:** `/my/digest` shows the next digest in the browser. This lets portfolio visitors see the feature without receiving email.
- **In-app notifications** (database channel, under a bell icon): "X started following you", "Y rated your recipe", "Z posted a new recipe".
- **Email preferences:** weekly digest (on), new follower (off), new rating (off).
- No email is ever sent to the demo user or sample creators.

### B3.8 Sharing
- Public and unlisted recipes can be shared. A private recipe's share button explains that it must be made public or unlisted first.
- Unlisted recipes always share the `/r/{token}` link.
- Native **Web Share API** where supported. Fallback share sheet with:
  - Copy link (with a "Copied!" toast)
  - Email (`mailto:` with a prefilled subject and body)
  - Facebook, X, Pinterest (with image), WhatsApp, Reddit
- **Email a friend** (members only): enter an email address and an optional short note, and we send a branded email containing the recipe card. Queued, rate limited (5 per hour per member), and not available to the demo user.
- Social preview images use the hero image.

### B3.9 Creator profiles
- `/creators/{uuid}` shows the avatar, bio, follower and public-recipe counts, a follow button, and the creator's public recipes (filterable).
- A profile with no public recipes shows a friendly empty state. There are no private profiles.

### B3.10 Homepage
- **Guests:** a hero with a playful headline and a big search box, meal-type and collection quick chips, Trending, New this week, a featured creator, and a call to action (sign up, or "Try the demo" in demo mode).
- **Members:** "From people you follow", recently saved, then Trending.
- **Featured creator** is chosen by rule: the creator with the most followers among those who published a public recipe in the past 30 days. Cached daily.

### B3.11 Static & error pages
- **Privacy policy** at `/privacy-policy`: lean, about eight short sections (§B14).
- No About, Contact, Terms or Cookies pages. There is no support mailbox.
- Custom 403, 404, 419, 429, 500 and 503 pages in the brand style.
- PWA metadata: `site.webmanifest` and favicons.
- No cookie banner: the app uses only essential cookies (session, CSRF, remember-me) and `localStorage` for the theme.

### B3.12 Footer
Every layout ends with this footer, with a Privacy Policy link added:

```blade
<footer class="site-footer">
    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
    <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
    <span>Developed by <a href="https://chaseterry.com" rel="noopener" target="_blank">Chase Terry<span class="visually-hidden"> (opens in a new tab)</span></a></span>
</footer>
```

`.visually-hidden` is a utility class in the design system (§B8).

## B4. Permissions matrix

| Action | Guest | Member (non-owner) | Owner | Demo user |
|--------|:-----:|:------------------:|:-----:|:---------:|
| View public recipe | ✓ | ✓ | ✓ | ✓ |
| View unlisted recipe | link only | link only | ✓ | as member |
| View private recipe | 404 | 404 | ✓ | as member |
| Create recipe | — | ✓ | — | ✓ (limit 25, no uploads) |
| Edit / delete recipe | — | — | ✓ | own only |
| Save recipe | → login | ✓ (public/unlisted) | ✗ | ✓ |
| Rate recipe | → login | ✓ if saved | ✗ | ✓ if saved |
| Follow creator | → login | ✓ | ✗ (self) | ✓ |
| Share link / social | public & unlisted | public & unlisted | public & unlisted | ✓ |
| Email a friend | — | ✓ | ✓ | ✗ |

Everything is enforced with **Policies** (`RecipePolicy`, `RatingPolicy`, `UserPolicy`) and query scopes (`Recipe::listed()`, `Recipe::visibleTo($user)`), never with inline checks.

## B5. Data model (proposed)

```
users
  id, uuid (unique), name, email (unique), email_verified_at,
  password (nullable: Google-only and sample accounts), avatar_path, bio, timezone,
  google_id (nullable, unique), digest_day (0–6, default 0), digest_hour (0–23, default 9),
  email_preferences (json), is_demo (bool), is_sample (bool),
  remember_token, timestamps
  -- is_demo and is_sample are never mass assignable

recipes
  id, uuid (unique), user_id, title, share_token (unique), summary,
  visibility (enum: private|unlisted|public), status (enum: draft|complete),
  image_path, prep_minutes, cook_minutes, total_minutes (stored generated column),
  servings, difficulty, meal_type, cuisine, method, occasion (all enums, nullable),
  calories, notes, video_url, rating_avg (decimal 3,2), rating_count, saves_count,
  published_at, seed_key (nullable, unique — links a row to the demo seed data),
  timestamps
  INDEX (visibility, published_at), FULLTEXT (title, summary)

ingredients          id, uuid, recipe_id, position, section, quantity (string), unit, name, note
steps                id, uuid, recipe_id, position, body
recipe_collection    recipe_id, collection (enum value)      PK (recipe_id, collection)
recipe_dietary_tag   recipe_id, tag (enum value)             PK (recipe_id, tag)
saves                id, uuid, user_id, recipe_id, timestamps, UNIQUE (user_id, recipe_id)
ratings              id, uuid, user_id, recipe_id, stars (1–5), timestamps, UNIQUE (user_id, recipe_id)
follows              id, uuid, follower_id, followee_id, timestamps, UNIQUE pair; follower ≠ followee
notifications        (Laravel default — its primary key is already a UUID)
jobs, failed_jobs, cache, sessions, password_reset_tokens   (Laravel framework tables)
```

### B5.1 Identifiers
- **Every model-backed table has an auto-increment `id` primary key and a unique `uuid` column.** `id` is used only inside the database, for foreign keys and joins.
- **`uuid` is the only identifier that leaves the server.** URLs, route model binding, Livewire action arguments (`wire:click="remove('{{ $ingredient->uuid }}')"`), form values, JSON-LD, emails and signed links all use the UUID. An `id` never appears in HTML, JavaScript or a URL.
- Models use Laravel's `HasUuids` trait with `uniqueIds()` returning `['uuid']` (so `id` stays the primary key), and `getRouteKeyName()` returns `'uuid'`. A shared `HasPublicUuid` trait or base model sets this once.
- UUIDs are Laravel's ordered UUIDs (UUIDv7), which index well in MySQL. Stored as `char(36)`, or `binary(16)` if indexing needs it.
- Records are never looked up by `id` from user input. Livewire components hold the model itself or a `#[Locked]` UUID.
- Exceptions: the two enum join tables (`recipe_collection`, `recipe_dietary_tag`) have no model and are never addressed on their own, so they have neither `id` nor `uuid`. Laravel's framework tables keep their defaults.
- Recipes and users have no slugs or handles.

Enums (PHP backed, with `label()` and `options()` helpers): `Visibility`, `RecipeStatus`, `MealType`, `Collection`, `Cuisine`, `Method`, `Difficulty`, `Occasion`, `DietaryTag`, `RecipeSort`. The vocabularies start from §A4.

Conventions: casts on models; `$fillable` (never `$guarded = []`); factories for every model; foreign keys with `cascadeOnDelete`; multi-row writes in transactions.

## B6. Routes (proposed)

```
GET  /                              home
GET  /discover                      discover (query-string filters)
GET  /collections/{collection}      collection.show
GET  /recipes/{recipe:uuid}         recipe.show      (public; owner sees any)
GET  /r/{token}                     recipe.shared    (unlisted share link)
GET  /creators/{user:uuid}          creator.show
GET  /privacy-policy                privacy-policy
--- auth ---
GET  /recipes/new                   recipe.create
GET  /recipes/{recipe:uuid}/edit    recipe.edit
GET  /my/recipes                    my.recipes
GET  /my/cookbook                   my.cookbook
GET  /my/following                  my.following       (feed + list)
GET  /my/notifications              my.notifications
GET  /my/digest                     my.digest          (digest preview)
GET  /settings/profile|account|notifications
--- auth scaffolding ---
/login  /forgot-password  /reset-password/{token}  /verify-email
/register                           (404 in demo mode)
/auth/google/redirect  /auth/google/callback
GET  /unsubscribe/{user:uuid}       signed
```

## B7. Architecture & best practices

- **Versions:** the latest stable Laravel, Livewire and PHP at scaffold time (expected Laravel 13.x, Livewire 4.x, PHP 8.4+; confirm when scaffolding). Alpine comes bundled with Livewire, so do not install it separately.
- **Starter:** a plain `laravel new` with **no starter kit**, because the kits ship Tailwind and Flux. Auth screens are our own Livewire components backed by Laravel's auth primitives.
- **Local development:** Laravel Sail with MySQL and Mailpit. **Production:** MySQL.
- **Configuration:** app-specific settings live in `config/relay.php` (demo mode, demo credentials, Turnstile keys, recipe limits) and are read with `config()`, never `env()`, outside config files.
- **Livewire usage:** full-page components for screens; small nested components only where state is isolated (rating widget, save button, follow button). Form objects for validation, `#[Url]` for filters, `#[Computed]` for queries, `#[Locked]` on every server-trusted property such as IDs, `wire:navigate` for SPA-like navigation, lazy loading for below-the-fold strips.
- **Alpine usage:** purely client-side behavior — servings scaler, check-offs, cook mode, share sheet, theme toggle, Turnstile widget, dropdowns, modals, toasts, drag-to-reorder.
- **Domain layer:** `app/Actions/*` for writes (`SaveRecipe`, `UnsaveRecipe` — which also removes the rating, `RateRecipe`, `ToggleFollow`, `SendWeeklyDigest`). A single `RecipeFilters` class owns all filtering and sorting. Policies handle authorization.
- **Security:** policies everywhere, allow-listed sorts, escaped text (no raw HTML anywhere), no raw video HTML, unguessable unlisted links, signed unsubscribe URLs, rate limiting, image validation and re-encoding (strips EXIF), and security headers (CSP, `X-Content-Type-Options`, `Referrer-Policy`). `TRUSTED_PROXIES` setting for running behind Cloudflare.
- **Media:** images on the `public` disk. Resized into a few widths (WebP) by a queued job using Intervention Image; served with `srcset` and `loading="lazy"`.
- **Mail and queues:** `database` queue. Mailpit locally; a transactional provider (Resend, as in ct-billify) in production. Markdown mail templates styled to the brand.
- **Scheduling** (`routes/console.php`):
  - `digest:send` hourly
  - `demo:reset` daily at 00:00 in `DEMO_TIMEZONE`, only when demo mode is on
  - `auth:clear-resets` hourly
  - `model:prune` daily (old notifications)
- **Performance:** eager-load card data; cache homepage strips and the featured creator; database indexes on filter columns.
- **Code style:** Pint (Laravel preset), Larastan (level 6+). Strict types where practical.

## B8. Design system (custom CSS, no frameworks)

**Constraints:** no Tailwind, no Bootstrap, no daisyUI, and no component kits. Hand-written modern CSS, bundled by Vite.

**CSS architecture**
- `resources/css/` uses cascade layers: `@layer reset, tokens, base, layout, components, utilities;`
- **Design tokens** as custom properties: color, type scale (fluid `clamp()`), spacing scale, radii, shadows, motion durations and easings, z-index.
- Native CSS nesting, `:has()`, container queries for cards, `color-mix()` for tints, and `prefers-color-scheme` for dark mode (with a manual Light / Dark / System toggle stored in `localStorage`).
- Component class names in a light BEM-ish style (`.card`, `.card__title`, `.chip.is-active`). One file per component, imported into `app.css`. Utilities include `.visually-hidden`.
- Blade components (`<x-button>`, `<x-card>`, `<x-chip>`, `<x-star-rating>`, `<x-modal>`, `<x-toast>`, `<x-avatar>`, `<x-field>`) keep markup and styling consistent.
- Icons: Blade SVG icon components drawn in one consistent rounded-stroke style.

**Brand (no existing branding; this is the starting direction)**
- **Name and mark:** "Recipe Relay". Logo idea: a wooden spoon passed like a relay baton, with a small motion swoosh, in a rounded badge. It must work in one color for the favicon.
- **Personality:** warm, playful and tidy — "a bright kitchen on a Saturday morning".
- **Palette (starting point; final values must pass AA contrast):**
  - Primary — Tomato `#E8553D`
  - Secondary — Basil `#3E8E5E`
  - Accent — Butter `#F6C445`
  - Surfaces — Cream `#FFF8EE` and white
  - Text — Charcoal `#2B2622`
  - Dark mode uses a warm charcoal surface (around `#1F1B18`), not pure black.
- **Type:** a rounded, friendly display face for headings ("Fredoka" or "Nunito") and a highly legible sans for body text ("Atkinson Hyperlegible" or "Inter"). Fonts are self-hosted. Base size 18px.
- **Shapes:** generous radius (16–24px cards, pill buttons and chips), soft layered shadows.
- **Motion:** springy hover lifts on cards, a heart "pop" on save, a star sparkle on rating, and a small confetti burst on the first published recipe. All of it is disabled under `prefers-reduced-motion`.
- **Illustration:** friendly food doodles for empty states and error pages, such as an empty pot for 404.
- **Layout:** mobile-first; card grid with `repeat(auto-fill, minmax(16rem, 1fr))`; sticky top bar plus a bottom tab bar on mobile (Home, Discover, Add, Cookbook, Me). No page scrolls sideways at 320px wide.

**Accessibility checklist**
- Semantic landmarks, a skip link, and visible focus rings (`:focus-visible`).
- Stars use radio inputs underneath for keyboard and screen-reader support.
- Every icon-only button has an `aria-label`. Toasts use `aria-live`.
- Minimum touch target 44×44px. Forms have labels, inline errors and `aria-describedby`.
- Links that open a new tab say so to screen readers (see the footer).
- Images require alt text; the recipe form prompts for it.
- Both themes meet contrast requirements, and the Turnstile widget follows the active theme.

## B9. Seed data

The launch content comes from the 21 legacy recipes in `archive-2025:database/data/recipes.json` and their photos in `public/demo/`.

- Converted into a PHP data file (`database/data/recipes.php` or similar) in the new schema: hours and minutes become prep and cook minutes; each recipe gets a meal type, collections and dietary tags; each gets a stable `seed_key`.
- **Sample creators:** about four fictional creators with friendly names, avatars and bios. They own most of the recipes. They have no password, so nobody can log in as them.
- **Demo user:** owns about five recipes in a mix of public, unlisted and private, follows two sample creators, and has saved and rated a few recipes.
- Sample creators rate and save each other's recipes so averages and counts look real.
- **Dates are relative to the seed time.** A few recipes are published "this week", so the following feed, Trending and the digest preview always have content.
- `php artisan db:seed` builds the same data as `demo:reset` (sample creators and recipes always; the demo user when its credentials are set).
- Seeders contain no real people and no fixed passwords.

## B10. Testing strategy
- **Pest** for everything: feature tests for Livewire components with `Livewire::test`, unit tests for actions and `RecipeFilters`.
- Must-have coverage:
  - **Visibility:** private and unlisted recipes never appear in Discover, search, collections, profiles, feeds, digests, the sitemap or JSON-LD; `/recipes/{uuid}` returns 404 for unlisted recipes; share tokens work and can be reset.
  - **Identifiers:** routes bind on `uuid`; a numeric `id` in a URL or Livewire action returns 404; rendered pages contain no `id` values.
  - Every policy.
  - Each filter and sort, and collection auto-ticking.
  - **Ratings:** require a save; one per member; average recalculated; unsave removes the rating.
  - Follow and unfollow.
  - Digest: composition, skipping, scheduling by timezone, unsubscribe signature, preview page.
  - **Demo mode:** registration 404 and hidden, demo button and Turnstile, demo restrictions enforced on the server, Google sign-in cannot create accounts, `demo:reset` restores everything and leaves real users' data alone; all of it absent when demo mode is off.
  - `user:create`: validation, reserved demo email, non-interactive refusal.
  - Turnstile: success, failure, unreachable, test keys rejected in production.
  - Image upload validation.
- Mail and notifications asserted with `Mail::fake()` and `Notification::fake()`; Turnstile with `Http::fake()`.
- CI: a GitHub Actions workflow on each PR running Pint `--test`, Larastan and Pest.

## B11. Demo mode

One setting, `DEMO_MODE` (exposed as `config('relay.demo_mode')`), prepares the app for the portfolio site. It defaults to **off** in config, and `.env.example` turns it on.

```dotenv
# Demo mode for the portfolio site. demo:reset creates the demo account from these values.
DEMO_MODE=true
DEMO_NAME="Demo User"
DEMO_EMAIL=demo@example.com
DEMO_PASSWORD=password
DEMO_TIMEZONE="America/Chicago"

# Cloudflare Turnstile test keys (always pass). Replace in production.
TURNSTILE_SITE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
```

### B11.1 What changes

| Area | Demo mode on | Demo mode off |
|------|--------------|---------------|
| **Registration** | Closed. `/register` returns 404 (an `EnsureRegistrationEnabled` middleware) and every link to it is hidden. | Open, protected by Turnstile. |
| **Google sign-in** | Signs in existing accounts only. | Can create accounts. |
| **Login page** | Shows a "Try the demo" panel: a button that signs in as the demo user, protected by Turnstile, and the note "The demo data resets every night." The credentials are not displayed. | No demo panel. |
| **Homepage call to action** | "Try the demo" | "Sign up" |
| **Nightly reset** | `demo:reset` runs daily at midnight in `DEMO_TIMEZONE`. | Not scheduled. |
| **Adding real accounts** | `php artisan user:create` | Registration or `user:create` |

### B11.2 Demo login
- The button calls a Livewire action that validates a Turnstile token (`appearance: 'interaction-only'`, so the widget stays hidden unless Cloudflare wants a click), then logs in with the configured credentials.
- Turnstile tokens are single-use, so the widget resets after every attempt.
- The action returns 404 when demo mode is off or the demo credentials are not set.
- Rate limited per IP.

### B11.3 Demo user restrictions
These apply to any user with `is_demo = true`. Controls are hidden in the UI **and** enforced on the server (an `EnsureNotDemo` guard), with a friendly message such as "That's turned off for the demo account."

- Cannot change email or password, or delete the account.
- Cannot request a password reset. The forgot-password page shows the usual neutral message and sends nothing.
- **Cannot upload files.** Instead of uploading an avatar or recipe photo, the demo user picks from a built-in gallery of the sample food photos.
- Cannot use "Email a friend", and is never sent email (digest, notifications). The digest preview page still works.
- Can create up to 25 recipes.
- Can freely create, edit and delete recipes, change visibility, save, rate, follow, and edit name, bio, timezone and preferences. The nightly reset restores everything.

### B11.4 `demo:reset`
Restores the demo world in one transaction:

1. Creates or updates the demo user from the `DEMO_*` values, marked `is_demo` and verified. It refuses (and exits with an error) if a regular account already uses `DEMO_EMAIL`.
2. Creates or restores the sample creators.
3. Deletes the demo user's recipes that are not seed recipes, and their saves, ratings, follows and notifications.
4. Restores every seed recipe from the seed data (matched on `seed_key`): fields, ingredients, steps, collections, tags, visibility, image and relative publish dates. Deleted seed recipes are recreated.
5. Restores the demo user's and sample creators' baseline saves, ratings and follows, and the demo user's profile and preferences.
6. Recalculates rating and save counters.
7. Rotates the demo user's `remember_token` and deletes their sessions, so visitors start fresh.

**Real accounts** (made with `user:create` or registration) keep their recipes. Their saves and ratings on seed recipes survive a reset, unless the demo user deleted that seed recipe during the day.

**Known risk:** anything the demo user makes public can be seen by all visitors until the next reset. Turnstile, rate limits, the 25-recipe limit and the lack of uploads keep this small. If abuse happens, a later option is to make the demo user's own new recipes private-only.

## B12. `php artisan user:create`

Creates an account from the console, which works even while registration is closed. Modeled on ct-billify's command.

```text
php artisan user:create
    {--name=     : Display name}
    {--email=    : Email address used to log in}
    {--timezone= : IANA timezone, such as America/Chicago (default UTC)}
```

- Uses Laravel Prompts. Options skip their questions.
- **The password is always prompted** (twice, to confirm) and is never accepted as an option, so it never lands in shell history. The command refuses to run in a non-interactive terminal.
- Validates everything with the same rules as registration: unique email, valid timezone, and `Password::defaults()`. Each answer is validated as it is typed.
- The demo email is reserved and rejected, because `demo:reset` refuses to overwrite a real account.
- The new account is marked email-verified, so it can publish right away.
- Prints "Created {email}. They can log in now."

## B13. Cloudflare Turnstile
- Used on **registration** (when demo mode is off) and the **demo login button** (when demo mode is on).
- Verified on the server against the `siteverify` endpoint by a small custom validation rule (`App\Rules\Turnstile`). No package.
- Fails closed: any error, timeout or unexpected response counts as a failed check.
- Cloudflare's published test keys are used locally and in CI. In production a test secret counts as a failed check, so a missing real key can never let every bot through.
- The Cloudflare script is loaded only on pages that render the widget, by an Alpine `turnstile` component that follows the active theme.

## B14. Privacy policy
A lean page at `/privacy-policy`, linked from the footer. Sections:

1. **About this application** — a portfolio project, not intended for public use; demo data resets nightly.
2. **Information we collect** — account details (name, email, hashed password, timezone), profile content, recipes and photos, saves, ratings and follows; Google profile details if Google sign-in is used.
3. **How we use it** — to run the app, show your content as you choose (private, unlisted or public), and send the emails you opt into.
4. **Sharing** — never sold. Shared only with the services that run the app: Cloudflare Turnstile (bot check), the email provider, and Google if you sign in with it. Social share buttons only send data to those networks when you click them.
5. **Cookies and local storage** — essential cookies only (session, CSRF, remember me); `localStorage` for the theme setting.
6. **Retention and deletion** — data is kept until you delete your account, which removes it. Demo account data is erased every night.
7. **Children's privacy**
8. **Changes to this policy** — with a "last updated" date.

There is no support email address.

## B15. Milestones

| # | Milestone | Scope |
|---|-----------|-------|
| M0 | Foundation | Archive branch, fresh scaffold, Sail, CI, Pint/Larastan/Pest, `config/relay.php`, design tokens and base CSS, layout shell and footer, privacy policy |
| M1 | Accounts | Login, registration (Turnstile), Google, verification, reset, profile and settings, avatars, `user:create` |
| M2 | Recipes | Model, editor (ingredient and step reordering), recipe page, visibility and share links, policies, images |
| M3 | Discover | Search, filters, collections and auto-ticking, sort, shared filter component, collection pages, homepage |
| M4 | Social | Cookbook (saves), ratings tied to saves, follows, following feed, in-app notifications |
| M5 | Relay | Share sheet, email a friend, OG and JSON-LD, weekly digest, preferences, unsubscribe, digest preview |
| M6 | Demo | Seed data, sample creators, `demo:reset` and schedule, demo login, demo restrictions, photo gallery |
| M7 | Polish | Cook mode, servings scaler, print CSS, error pages, accessibility audit, deployment |

## B16. Non-goals for v1
- Friends, friend requests and private profiles (dropped from Recipeek).
- Admin area, moderation and reports.
- Rating comments, recipe comment threads, direct messages.
- Meal planning, shopping lists, nutrition calculation, multiple languages.
- Social logins other than Google.
- Native mobile apps (an installable PWA is a later option).

---

## Decisions log

Answers to the v0.1 open questions (2026-11-09):

| # | Question | Decision |
|---|----------|----------|
| 1 | Friends & private profiles | Dropped. Recipes are public or private (plus unlisted); following is one-way. |
| 2 | Third visibility level | **Unlisted** (anyone with the link) is in v1. |
| 3 | Rating eligibility | Must have saved the recipe. Unsaving removes the rating. No "I made this" button. |
| 4 | Rating comments | Stars only. |
| 5 | Social login | Google only. |
| 6 | Digest timing | User-selectable, default Sunday 09:00 in the user's timezone. |
| 7 | Collections | Fixed list. Creators tick checkboxes; some are pre-ticked by rule. |
| 8 | Database & hosting | Sail with MySQL locally; MySQL in production. |
| 9 | Testing | Pest. |
| 10 | Cutover & data | Legacy site is not running; no data to migrate. Keep the legacy seed recipes for launch content. |
| 11 | Branding | None yet; starting direction in §B8. |
| — | Identifiers | Every record has `id` (internal) and `uuid`; only the UUID appears in URLs and the UI. No slugs or handles. |
| — | Deployment context | Portfolio site, in demo mode, modeled on ct-billify: Turnstile-protected demo login, nightly reset, `user:create`, footer, lean privacy policy. |

## Open questions

1. **Production host.** Where will the portfolio deployment run (Forge, Laravel Cloud, or your own server)? This only affects deployment notes, not the build.
2. **Demo user's public recipes.** OK with the risk in §B11.4, where anything the demo user publishes is visible to all visitors until midnight? The alternative is to keep the demo user's new recipes private or unlisted only.

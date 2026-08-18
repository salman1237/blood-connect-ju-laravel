# Blood Connect JU

A blood donation coordination platform for Jahangirnagar University students, faculty, and staff — built as a Laravel 12 + Blade + Tailwind application.

Full project spec: [`claudePrompt.md`](claudePrompt.md). Build progress / phase log: [`.claude-progress.md`](.claude-progress.md).

Design reference: [blood-connect-ju.lovable.app](https://blood-connect-ju.lovable.app/) and its component source, [`salman1237/blood-connect-ju`](https://github.com/salman1237/blood-connect-ju) (a separate React/Tailwind prototype repo — used here only as the visual/UX source of truth, not shared code).

## Tech stack

- Laravel 12, Blade (server-rendered)
- Tailwind CSS v3 + Alpine.js
- MySQL (`utf8mb4_unicode_ci`)
- Laravel Breeze (auth scaffolding)

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the database (MySQL, matching `.env`):

```sql
CREATE DATABASE blood_connect_ju CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then:

```bash
php artisan migrate
npm run build   # or `npm run dev` while working on views
php artisan serve
```

## Testing

```bash
php artisan test
vendor/bin/pint        # code style
```

CI (`.github/workflows/tests.yml`) runs both on every push/PR to `main`.

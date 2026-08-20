# Blood Connect JU

A blood donation coordination platform for Jahangirnagar University students, faculty, and staff — built as a Laravel 12 + Blade + Tailwind application.

**Live:** [blood.deshlet.com](https://blood.deshlet.com)

Full project spec: [`claudePrompt.md`](claudePrompt.md). Build progress / phase-by-phase log: [`.claude-progress.md`](.claude-progress.md) — the detailed record of every decision, deviation from the brief, and bug found along the way.

Design reference: [blood-connect-ju.lovable.app](https://blood-connect-ju.lovable.app/) and its component source, [`salman1237/blood-connect-ju`](https://github.com/salman1237/blood-connect-ju) (a separate React/Tailwind prototype repo — used here only as the visual/UX source of truth, not shared code).

## What it does

- **Emergency requests** — post a request for a blood group, urgency, and hospital; a live feed sorts by urgency then recency and filters by blood group/hall.
- **Matching** — donors are ranked by real ABO/Rh compatibility (not just exact match), then by shared hall/department, then availability. Compatible available donors get notified automatically when a matching request goes up.
- **Response & confirmation** — donors respond with "I can donate," the requester picks who helped, and once fulfilled both sides independently confirm the donation happened before it's logged.
- **Trust & safety** — a verifier queue for approving/rejecting requests before they go live, and a report/flag system with an admin moderation queue.
- **Engagement** — badges (first donation, 5-time donor, rare blood type), a donation history timeline, and a hall/department leaderboard.
- **Notifications** — every meaningful moment (matching request, response, selection, verification, donation confirmation, periodic eligible-donor reminders) emails and in-app-notifies the right person, with a per-user opt-out.
- **Admin dashboard** — platform stats, searchable/filterable user management (role changes, deactivation), and the moderation queue.
- **Localization** — EN/বাংলা toggle, persisted per-session and per-account.

## Tech stack

- Laravel 12, Blade (server-rendered)
- Tailwind CSS v3 + Alpine.js
- MySQL (`utf8mb4_unicode_ci`)
- Laravel Breeze (auth scaffolding) + Socialite (Google OAuth)
- Laravel's native notification system (`database` + `mail` channels)

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
php artisan migrate --seed   # --seed for a populated demo dataset (see below)
npm run build                # or `npm run dev` while working on views
php artisan serve
```

### Optional configuration

- **Google sign-in**: set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` in `.env`. Without these, email/password auth still works fine — the "Continue with Google" button just won't.
- **Mail**: defaults to `MAIL_MAILER=log` (emails write to `storage/logs/laravel.log` instead of sending). Set real SMTP credentials to send for real.
- **Queue**: `QUEUE_CONNECTION=database` by default. Notifications are queued, so either run `php artisan queue:work` in a separate terminal locally, or process the queue synchronously for quick testing with `php artisan queue:work --stop-when-empty`. In production (shared hosting, no persistent worker process) this is instead drained by `queue:work --stop-when-empty` on the same cron minute that runs the scheduler — see `routes/console.php`.

### Seed data

`php artisan migrate --seed` (or `php artisan db:seed` against an already-migrated database) creates a full demo dataset: an admin, two verifiers, and thirteen donors spread across roles/blood groups/halls/departments, the three sample requests from the brief (matching what's shown on the landing page), some donation history, and a few earned badges. Every seeded account's password is `password`.

```
admin@juniv.edu           — admin
farhana.verifier@juniv.edu — verifier
rahim.uddin@juniv.edu      — student donor (O-, Al Beruni Hall)
```

## Testing

```bash
php artisan test
vendor/bin/pint        # code style
```

CI (`.github/workflows/tests.yml`) runs both on every push/PR to `main`, gating `.github/workflows/deploy.yml` — production only deploys on a green build.

## Scheduled jobs

Two scheduled commands, registered in `routes/console.php`:

- `requests:expire` (hourly) — auto-expires open requests past their 72h window.
- `donors:remind-eligible` (weekly) — emails donors who are eligible to donate again and haven't been reminded in the last 30 days.

Both need `php artisan schedule:run` on a real cron (every minute) to actually fire — already wired up on production.

## Notable design decisions

The full reasoning for every non-obvious call lives in [`.claude-progress.md`](.claude-progress.md), phase by phase. A few worth knowing up front:

- **No university-email restriction** — the brief's `ALLOWED_EMAIL_DOMAIN` gate was dropped by explicit direction; any valid email can register.
- **Donor selection vs. mutual confirmation are separate steps** — a requester picking a responder ("this is my donor") is distinct from both sides later confirming the donation actually happened, which is what triggers `donation_history` and the trust-score bump.
- **"Deactivate" never deletes** — an admin deactivating a user just flips a flag; the account's donation history, requests, and badges all survive, since those cascade-delete on a real row deletion.
- **SMS was dropped in favor of email** — the original brief's SMS-channel stub was replaced with real email notifications throughout, by later direction.
- **Production mail deliverability**: SPF/DKIM/DMARC are all correctly published, but mail currently lands in spam due to a PTR/HELO hostname mismatch at the hosting network level — outside anything this app or cPanel can fix. Flagged, accepted as-is for now.

## Deployment

Pushes to `main` deploy automatically to production over SSH (`.github/workflows/deploy.yml`) once the test suite passes. See `.claude-progress.md`'s "Infrastructure" section for the full hosting/CI-CD setup and a security-incident writeup unrelated to this app's own code.

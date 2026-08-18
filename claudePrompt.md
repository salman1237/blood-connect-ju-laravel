# Blood Connect JU — Claude Code Build Prompt

Full project spec for building "Blood Connect JU" — a blood donation coordination platform for Jahangirnagar University students, faculty, and staff — as a real Laravel application.

This doc is meant to be handed to Claude Code as the project brief. Reference design: the approved landing page copy/layout at `blood-connect-ju.lovable.app` (calm civic-tech style, red accent on off-white, bilingual EN/বাংলা).

---

## 0. Reference Materials

Claude Code should treat these as the visual/UX source of truth and translate them into Laravel + Blade — not reinvent the design:

- **Live prototype:** https://blood-connect-ju.lovable.app/
- **Frontend reference repo (Lovable-exported React + Tailwind + shadcn/ui):** https://github.com/salman1237/blood-connect-ju — this is the actual component/layout source. Match its page structure, spacing, color tokens, card designs, and — critically — its responsive breakpoint behavior when building the Blade + Tailwind views. Don't port the React/TypeScript code directly (different stack), but treat every screen it contains as the pixel-level reference for the equivalent Blade view.
- **Additional design artifact:** https://claude.ai/code/artifact/5869ddaa-bba2-4f94-9595-bb77b471523c — open this directly in Claude Code (it's a rendered, JS-based artifact that doesn't expose its content to a plain fetch) and use it as a further design reference alongside the two links above.

---

## 1. Tech Stack

- **Framework:** Laravel 12
- **Templating:** Blade (server-rendered, no separate frontend framework)
- **Styling:** Tailwind CSS via Laravel Vite plugin
- **Interactivity:** Alpine.js for light client-side behavior (dropdowns, toggles, live filter UI) — no full JS framework needed
- **Database:** MySQL
  - Database name: `blood_connect_ju`
  - Charset/collation: `utf8mb4` / `utf8mb4_unicode_ci` on the database and every text/varchar column — required for Bangla (বাংলা) text to store correctly
- **Auth:** Laravel's built-in auth scaffolding (Breeze, Blade edition) as the base, extended with role-based middleware and a university-email restriction
- **Notifications:** Laravel's native notification system (`database` channel) for in-app notifications; stub a `SmsChannel` interface for future SMS gateway integration (e.g. Twilio or a local BD provider) — don't wire a real SMS provider yet
- **Localization:** Laravel's built-in localization (`lang/en`, `lang/bn`) for the English/বাংলা toggle

---

## 2. Environment / Setup

```
composer create-project laravel/laravel blood-connect-ju
cd blood-connect-ju
composer require laravel/breeze --dev
php artisan breeze:install blade
```

`.env` database config:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blood_connect_ju
DB_USERNAME=root
DB_PASSWORD=
```

Add a config value for the allowed university email domain (don't hardcode it — make it an env var so it's easy to correct):
```
ALLOWED_EMAIL_DOMAIN=juniv.edu
```

---

## 3. User Roles

| Role | Description |
|---|---|
| `student` / `staff` / `faculty` | Default roles on signup. Can register as a donor, post requests, browse/search donors, respond to requests. |
| `verifier` | CR / hall provost office / medical center staff. Can approve/verify pending requests, moderate flagged content. |
| `admin` | Full access — user management, analytics, moderation. |

Store `role` as an enum column on `users`. Use Laravel policies/middleware (`role:admin`, `role:verifier`) to gate routes — don't scatter `if` checks through controllers.

---

## 4. Database Schema (MySQL / Laravel migrations)

All tables: `utf8mb4_unicode_ci`, `id` as unsigned bigint primary key, `created_at`/`updated_at` timestamps unless noted.

### `users`
| Column | Type | Notes |
|---|---|---|
| name | varchar | |
| email | varchar, unique | must match `ALLOWED_EMAIL_DOMAIN` |
| password | varchar | hashed |
| role | enum('student','staff','faculty','verifier','admin') | default `student` |
| hall_or_department | varchar, nullable | |
| phone | varchar, nullable | |
| email_verified_at | timestamp, nullable | |

### `donor_profiles`
| Column | Type | Notes |
|---|---|---|
| user_id | FK → users, unique | one profile per user |
| blood_group | enum('A+','A-','B+','B-','AB+','AB-','O+','O-') | |
| last_donation_date | date, nullable | |
| is_available | boolean | default `true` |
| trust_score | integer | default `0` |

Eligibility is **computed**, not stored: a donor is eligible if `last_donation_date` is null or more than **120 days** ago (matches the figure already shown on the live landing page). Add a `getIsEligibleAttribute()` accessor on the model rather than a stored column, so it never goes stale.

### `blood_requests`
| Column | Type | Notes |
|---|---|---|
| requester_id | FK → users | |
| blood_group | enum (same list as above) | |
| units_needed | unsigned tinyint | |
| hospital_name | varchar | |
| location | varchar, nullable | |
| urgency | enum('critical','within_24h','planned') | |
| patient_context | text, nullable | |
| contact_method | varchar | |
| status | enum('open','donor_found','fulfilled','expired') | default `open` |
| is_verified | boolean | default `false` |
| verified_by | FK → users, nullable | |
| expires_at | timestamp, nullable | auto-expire via scheduled job |

### `request_responses`
| Column | Type | Notes |
|---|---|---|
| request_id | FK → blood_requests | |
| donor_id | FK → users | |
| status | enum('responded','confirmed','declined') | default `responded` |

### `donation_history`
| Column | Type | Notes |
|---|---|---|
| donor_id | FK → users | |
| request_id | FK → blood_requests, nullable | |
| confirmed_at | timestamp | |

### `badges`
| Column | Type | Notes |
|---|---|---|
| name | varchar | e.g. "5-Time Donor" |
| slug | varchar, unique | |
| description | varchar | |

### `donor_badges` (pivot)
| Column | Type | Notes |
|---|---|---|
| donor_id | FK → users | |
| badge_id | FK → badges | |
| earned_at | timestamp | |

### `notifications`
Use Laravel's standard morphable notifications table (`php artisan notifications:table`) — don't hand-roll this one.

---

## 5. Feature Modules

### 5.1 Auth & Onboarding
- Signup restricted to `ALLOWED_EMAIL_DOMAIN`, with a clear inline error if a non-university email is used
- Role selection at signup (student/staff/faculty)
- Email verification (Laravel's built-in `MustVerifyEmail`)
- Forgot / reset password (Breeze default, styled to match brand)
- Post-signup onboarding step to complete the donor profile (blood group, hall/department, availability)

### 5.2 Emergency Requests
- Post request form → creates `blood_requests` row, status `open`
- Live feed (home dashboard) of open requests, sorted by urgency then recency, filterable by blood group and hall/department
- Request detail page: full info, status tracker (Open → Donor Found → Fulfilled), list of responders
- Requester can mark a specific donor "confirmed" and mark the request Fulfilled
- Scheduled command (`php artisan requests:expire`) to auto-expire stale open requests (e.g. after 72h), run via the scheduler

### 5.3 Matching & Response
- On the request detail/feed, rank eligible donors by: blood-group compatibility → same hall/department → availability
- "I can donate" button creates a `request_responses` row and fires a notification to the requester
- Donor gets notified of new open requests matching their blood group (queued job, so it doesn't block the request-creation request cycle)

### 5.4 Trust & Safety
- Verifier role sees a queue of unverified requests (`is_verified = false`) with Approve/Reject actions
- "Report" button on any request → flags it for admin review (simple `flagged` boolean or a lightweight `reports` table if you want a paper trail)
- After a request is marked Fulfilled, both requester and donor get a mutual confirmation prompt; confirming both sides writes a `donation_history` row and bumps the donor's `trust_score`

### 5.5 Engagement
- Donation history timeline on the donor's own profile
- Badge system: award badges via model events/observers when donation-history milestones are hit (1st donation, 5th donation, rare-blood-type donation, etc.)
- Hall/department leaderboard — aggregate confirmed donations grouped by `hall_or_department`

### 5.6 Admin Dashboard
- Stat cards: donors by blood group, requests fulfilled vs expired, average response time
- User management table (search, role change, deactivate)
- Moderation queue for flagged requests

### 5.7 Localization & Settings
- Language toggle (EN / বাংলা) in the header, persisted per-session (and per-user if logged in)
- Settings page: language, notification preferences, edit profile, logout

---

## 6. Routes (high-level)

```php
// Guest
Route::get('/', LandingController::class);
Route::get('/login', ...); Route::post('/login', ...);
Route::get('/signup', ...); Route::post('/signup', ...);
Route::get('/forgot-password', ...); Route::post('/forgot-password', ...);
Route::get('/reset-password/{token}', ...); Route::post('/reset-password', ...);
Route::get('/verify-email', ...);

// Authenticated
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class); // live request feed
    Route::resource('requests', BloodRequestController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/requests/{request}/respond', [RequestResponseController::class, 'store']);
    Route::post('/requests/{request}/fulfill', [BloodRequestController::class, 'fulfill']);
    Route::get('/donors', DonorSearchController::class);
    Route::get('/profile', ProfileController::class);
    Route::get('/notifications', NotificationController::class);
    Route::get('/leaderboard', LeaderboardController::class);
    Route::get('/settings', SettingsController::class);
});

// Verifier
Route::middleware(['auth', 'role:verifier,admin'])->prefix('verify')->group(function () {
    Route::get('/queue', VerificationQueueController::class);
    Route::post('/requests/{request}/approve', ...);
    Route::post('/requests/{request}/reject', ...);
});

// Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class);
    Route::resource('users', AdminUserController::class)->except(['create', 'store']);
});
```

---

## 7. Blade View Structure

```
resources/views/
  layouts/
    app.blade.php          (authenticated shell — nav + language toggle)
    guest.blade.php         (public shell — landing/auth pages)
  partials/
    nav.blade.php
    footer.blade.php
    request-card.blade.php  (reusable — used on dashboard, request list, etc.)
  landing.blade.php
  auth/
    login.blade.php
    signup.blade.php
    forgot-password.blade.php
    reset-password.blade.php
    verify-email.blade.php
    onboarding.blade.php
  dashboard.blade.php
  requests/
    index.blade.php
    create.blade.php
    show.blade.php
  donors/
    index.blade.php
  profile/
    show.blade.php
  notifications/
    index.blade.php
  leaderboard.blade.php
  settings.blade.php
  verify/
    queue.blade.php
  admin/
    dashboard.blade.php
    users.blade.php
```

---

## 8. Design System Reference

Match the already-approved landing page (`blood-connect-ju.lovable.app`) and the frontend reference repo (see Section 0):
- One confident red as the sole accent/CTA color, used sparingly — primary buttons, urgency badges, the "Live" indicator
- Off-white background, charcoal text, clean sans-serif type
- Large, unambiguous type for blood group labels (A+, O-, etc.)
- Urgency badges: "Critical" in red, "Within 24h" in a calmer neutral tone
- "✓ Verified" badge on approved requests
- Mobile-first — bottom nav on small screens, sidebar on desktop
- Reuse the exact landing copy already live (hero headline, stats row, feature highlights, footer) rather than rewriting it

### Responsiveness — hard requirement

Every screen must be fully responsive, not just the landing page. Build mobile-first with Tailwind's breakpoint utilities (`sm:`, `md:`, `lg:`, `xl:`) and verify each screen at minimum against:

| Breakpoint | Width | What to check |
|---|---|---|
| Mobile | ~375px | Bottom nav, single-column layout, request cards stack vertically, forms full-width, no horizontal scroll |
| Tablet | ~768px | Nav transitions to sidebar or stays bottom (pick one and stay consistent), 2-column card grids where it fits |
| Desktop | ~1280px+ | Sidebar nav, multi-column dashboard/stat layouts, max content width so text lines don't stretch too wide |

Additional rules:
- Touch targets (buttons, nav items, form controls) at least 44×44px on mobile
- Tables (e.g. admin user management) collapse to stacked cards on small screens rather than causing horizontal scroll
- Images and icons scale via relative units, never fixed pixel widths that overflow small viewports
- Test the request-posting form and the admin dashboard specifically at all three breakpoints — they're the most layout-dense screens

---

## 9. Seed Data

Write a `DatabaseSeeder` that creates:
- A handful of users across each role (student, staff, faculty, verifier, admin) with donor profiles across different blood groups and halls/departments
- Sample open requests matching what's already live on the landing page, so the dashboard looks populated immediately:
  - O-, 2 units, Critical, Verified, Enam Medical College Hospital
  - B+, 1 unit, Within 24h, Verified, Savar Upazila Health Complex
  - A+, 3 units, Critical, Dhaka Medical College Hospital (unverified)
- A few `badges` rows (First Donation, 5-Time Donor, Rare Blood Type Donor)
- Some `donation_history` rows so the leaderboard and badges aren't empty on first load

---

## 10. Suggested Build Order (phases)

Build and test each phase before moving to the next — good checkpoints for a `.claude-progress.md` tracking file and git commits between phases.

1. **Phase 0 — Setup:** Laravel 12 project, MySQL connection to `blood_connect_ju`, Breeze auth scaffolding, Tailwind, base layouts matching the design system
2. **Phase 1 — Auth & roles:** signup/login, email-domain restriction, email verification, role column + middleware, onboarding step
3. **Phase 2 — Donor profiles:** `donor_profiles` table, eligibility accessor, profile page
4. **Phase 3 — Requests core:** `blood_requests` CRUD, dashboard feed, request detail, status lifecycle
5. **Phase 4 — Matching & response:** `request_responses`, "I can donate" flow, ranked donor list
6. **Phase 5 — Trust & safety:** verifier queue, report/flag, mutual fulfillment confirmation
7. **Phase 6 — Engagement:** `donation_history`, badges, leaderboard
8. **Phase 7 — Notifications:** in-app notifications via Laravel's notification system, SMS channel stub
9. **Phase 8 — Admin dashboard:** stats, user management, moderation queue
10. **Phase 9 — Localization & polish:** EN/বাংলা language files, settings page, seeders, final styling pass

---

## 11. Notes / Assumptions to confirm before building

- `ALLOWED_EMAIL_DOMAIN` is a placeholder (`juniv.edu`) — confirm the real JU institutional email domain before enforcing it
- SMS gateway is stubbed only — pick a provider (Twilio, or a local BD SMS gateway) before Phase 7 goes live
- 120-day donation eligibility window is taken from the already-approved landing page copy
- Before starting Phase 0, browse the frontend reference repo and design artifact in Section 0 so the Blade views are built against real screens, not assumptions

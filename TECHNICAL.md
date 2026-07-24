# uLam — Technical Operations Guide

Everything needed to run, deploy, and maintain uLam. Written for the operator (you),
so future-you doesn't have to reverse-engineer past-you.

**Repos:**
- Backend + Admin SPA: https://github.com/cessto103/ulam (Laravel 12, `c:\wamp64\www\uLam`)
- Mobile app: https://github.com/cessto103/ulam-app (Expo SDK 54, `c:\wamp64\www\uLam-app`)

---

## 1. Local Development (current setup)

| Piece | How it runs |
|---|---|
| API + web | WAMP64 Apache → `http://localhost/uLam/public` |
| Database | WAMP MySQL, db `ulam_db`. **All tables must be InnoDB** — `config/database.php` forces `engine => InnoDB` because WAMP's default (MyISAM) silently drops foreign keys and transactions. |
| Admin dashboard | Built SPA served at `http://localhost/uLam/public/admin-panel` (rebuild: `cd admin && npm run build`) |
| Mobile app | `cd uLam-app && npx expo start` → Expo Go on the same Wi-Fi, or the installed preview APK (points at `http://192.168.254.195/uLam/public`) |
| Phone can't reach API? | WAMP tray → **Put Online**; Windows Firewall must allow Apache port 80 (private networks); re-check the PC's LAN IP with `ipconfig` (DHCP can change it — it's baked into `eas.json` preview profile). |

Reset the database: `php artisan migrate:fresh --seed`
(owner accounts + admin role + legal docs + demo data all reseed automatically).

### Scheduled jobs in local dev

The Laravel scheduler only fires if something ticks it. On Windows dev, run:

```
php artisan schedule:work
```

(keep the window open — it ticks every minute), or run jobs manually:

```
php artisan ulam:maintenance          # seller sub/boost expiry + renewal reminders + OTP prune
php artisan billing:process-lifecycle # PayMongo subscription lifecycle
php artisan prices:refresh-ai         # AI market price refresh
php artisan prices:refresh-gov        # DA/DTI reference prices
php artisan ulam:daily-reminders      # daily spending-log reminder pushes
```

---

## 2. Deploying to Hosting (when the domain/host exists)

### 2.1 One-time server setup

1. PHP 8.2+, MySQL 8+ (InnoDB default), Composer.
2. Clone the repo; `composer install --no-dev --optimize-autoloader`.
3. Copy `.env.example` → `.env`, fill in production values (see §3), `php artisan key:generate` **only on first ever deploy** (losing APP_KEY breaks all encrypted columns — 2FA secrets!).
4. `php artisan migrate --force` then `php artisan db:seed --class=LegalDocumentSeeder` (and `SellerPlanSeeder`) — **not** the full DatabaseSeeder (that's demo data).
5. Point the web root at `/public`. Enable HTTPS (Let's Encrypt).
6. `php artisan storage:link` — **easy to miss, breaks every uploaded image if skipped.** Without this, `public/storage` never gets created, so any `/storage/...` URL (branding logos, favicon, theme background images, About page images, recipe/post photos) 404s in production even though the upload itself "succeeded" and looks fine in the admin locally. One-time only — re-running is a harmless no-op if the symlink already exists.
7. `php artisan config:cache && php artisan route:cache`  (re-run after every deploy).

### 2.2 CRON — scheduled jobs (REQUIRED in production)

Add **exactly one** cron entry (cPanel → Cron Jobs, or `crontab -e`):

```
* * * * * cd /path/to/ulam && php artisan schedule:run >> /dev/null 2>&1
```

**Hostinger shared hosting note:** the cron UI there won't accept "every minute" —
it caps the interval to prevent abuse. Use this instead, which is Laravel's own
documented fallback for hosts like this:

```
*/5 * * * * cd /home/u629998602/public_html/ulam && php artisan schedule:run >> /dev/null 2>&1
```

This still fires every job below on time, because every one of them is scheduled
at a time that's a multiple of 5 (`:00`) — `hourly()` and every `dailyAt('HH:00')`
below land on `:00`, `:05`, `:10`... which `*/5` always hits. **Only a caveat for
the future:** if a new job is ever scheduled at a non-5-minute-aligned time (e.g.
`dailyAt('06:03')` or `->everyMinute()`), it would silently never fire under `*/5`
— keep any new schedule times on `:00`/`:05`/`:10` etc. to stay safe on this host.

That single cron line ticks Laravel's scheduler; Laravel itself decides what
actually runs (see `routes/console.php`) — **do not** also add a direct cron
entry for any of the commands below (e.g. `0 2 * * * ... artisan prices:refresh-ai`
as its own row). Every one of them is already registered inside `routes/console.php`,
so a separate direct entry makes it run **twice** — once from its own line, once
again from `schedule:run` picking it up at the same minute. This actually happened
in production (found 2026-07-23): `ulam:maintenance`, `billing:process-lifecycle`,
`prices:refresh-ai`, `prices:refresh-gov`, and `ulam:daily-reminders` all had their
own direct cron rows in addition to `schedule:run`, silently doubling AI price-refresh
spend every night. **Exclusion list — never give these their own cron row:**
`ulam:maintenance`, `billing:process-lifecycle`, `prices:refresh-ai`, `prices:refresh-gov`,
`ulam:expire-strikes`, `ulam:weather-daily`, `ulam:daily-reminders` — all of them
belong to `schedule:run` alone.

| Command | Schedule | What it does |
|---|---|---|
| `ulam:maintenance` | hourly | Expires ended manual-GCash seller subscriptions (and re-syncs store visibility), expires ended boosts, sends "subscription ending in 3 days" reminders, prunes stale OTP codes |
| `billing:process-lifecycle` | hourly | PayMongo subscription grace/expiry/suspension |
| `prices:refresh-ai` | 02:00 | AI market price refresh (paused — see §3, `price_refresh_ai_enabled`) |
| `prices:refresh-gov` | 03:00 | DA Bantay Presyo / DTI SRP reference refresh (paused — see §3, `price_refresh_ai_enabled`) |
| `ulam:expire-strikes` | 04:00 | "Your record is clear" notification for recently-expired moderation strikes |
| `ulam:weather-daily` | 06:00 | Daily weather notification per user location (see §7 — free, no AI cost) |
| `ulam:daily-reminders` | 08:00 | Spending-log reminder notifications (push via Expo, same `NotificationService::sendBulk()` as §7 — no AI cost) |

**Without the `schedule:run` cron line, none of the above ever runs.** Symptoms of
a missing cron: subscriptions never expire, no renewal reminders, stale government
prices, no weather/reminder pushes.

### 2.3 Queue worker (emails, image moderation)

Queued jobs (welcome emails, image moderation) need a worker. On shared hosting
without supervisor, the pragmatic option is a second cron line:

```
* * * * * cd /path/to/ulam && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

On Hostinger (or any host that won't allow every-minute cron), use `*/5 * * * *`
here too, same reasoning as §2.2 — just a slightly longer worst-case delay before
a queued email/moderation job picks up.

On a VPS, use Supervisor with `php artisan queue:work --tries=3` instead.

### 2.4 Deploy checklist (every deploy)

```
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
cd admin && npm ci && npm run build        # if the admin SPA changed
```

---

## 3. Environment Variables That Matter

| Var | Notes |
|---|---|
| `APP_KEY` | NEVER regenerate in prod — encrypted columns (2FA secrets) become unreadable. Back it up. |
| `APP_URL` | Public API base URL (mobile app's `EXPO_PUBLIC_API_URL` must match). |
| `ANTHROPIC_API_KEY` | AI meal plans + AI price refresh (**not** weather — see §7, that's Open-Meteo, free). Two separate kill switches, no deploy needed: AppSettings `ai_meal_plans_enabled` (meal plan generation) and `price_refresh_ai_enabled` (nightly market + government price refresh). Set to `0` in this dev DB as of 2026-07-23 pending a cost review — check Admin → Content → Monetization → "AI feature controls" for the live state, don't assume this file reflects it. |
| `RESEND_API_KEY` | Email (OTP codes, welcome). **Sandbox only delivers to cessto103@gmail.com until a domain is verified at resend.com/domains** — do that at hosting time, then set the from-address to the domain. |
| `PAYMONGO_SECRET_KEY` / `PAYMONGO_WEBHOOK_SECRET` | Placeholders until PayMongo goes live; webhook needs a public HTTPS URL (`/api/billing/webhooks/paymongo`). |
| `MAIL_FROM_ADDRESS` | Switch to the real domain address once Resend is verified. |

---

## 4. Security Runbook

- **Admin account:** `cessto103@gmail.com` is the sole `role=admin`. 2FA (Google Authenticator) is enrolled. **Pre-launch: change the password to a strong unique one** (Admin → Settings → Account, or the app's forgot-password flow).
- **2FA lockout recovery** (lost/erased authenticator): from the server terminal,
  ```
  php artisan admin:reset-2fa cessto103@gmail.com
  ```
  This disables 2FA on that admin account **and revokes its dashboard sessions** (in case the phone was stolen, not lost). Log in with password only, then re-enroll immediately in Settings → Security. Having server access is the ownership proof — this is safe by design. Prevention: enable Google Authenticator's cloud sync (the cloud icon) so codes survive phone loss.
- **At hosting time:** serve the admin SPA + `/api/admin/*` on a separate subdomain (e.g. `admin.ulam.app`) and IP-allowlist it at the server/Cloudflare level. The mobile API stays public.
- **Production HTTPS:** re-tighten `uLam-app/src/api/client.ts` (currently allows plain HTTP for LAN test builds) and remove `usesCleartextTraffic` from `app.json` before a store release.
- Legal docs: public pages at `/legal/terms` and `/legal/privacy` — these URLs go on the Play Store listing. Manage content + versions in Admin → Content → Legal Documents; publishing a new version forces in-app re-acceptance.

---

## 5. Mobile Builds (EAS)

Project: expo.dev → `cessto103s-team/ulam` (id `3200d60d-716f-461f-aa45-fd7ff2b2c1b3`), package `com.ulam.app`.

```
cd uLam-app
npx eas build --platform android --profile preview     # sideloadable APK
npx eas build --platform android --profile production  # Play Store AAB (later)
```

- `.npmrc` (`legacy-peer-deps=true`) is required — EAS installs fail on the React 19 peer conflict without it.
- The **preview** profile bakes the LAN API URL; a production build needs `EXPO_PUBLIC_API_URL` set to the real HTTPS domain in `eas.json`.
- Keystore is managed by EAS (backup: `npx eas credentials`). Losing it (without Play App Signing) means never updating the app again.
- Version discipline: bump `version` + `versionCode`/`buildNumber` (`npm run check:metadata` verifies) before each build.

### Push notifications (not yet configured)

Remote push in standalone builds needs Firebase Cloud Messaging:
1. Firebase console → create project → add Android app `com.ulam.app` → download `google-services.json` into `uLam-app/`.
2. `app.json` → `android.googleServicesFile: "./google-services.json"`.
3. expo.dev project → Credentials → upload the FCM V1 service account key.
4. Rebuild the APK. (Code already handles registration and fails silently until then.)

### Maps

The locator map uses **Leaflet + OpenStreetMap tiles in a WebView** — no API key, works in Expo Go and APKs. If native maps are ever wanted (`react-native-maps` on Android), that requires a Google Cloud project with Maps SDK enabled + an API key in `app.json` → `android.config.googleMaps.apiKey`, and a rebuild.

---

## 6. Backups (production)

Minimum viable: nightly `mysqldump` + copy of `storage/app/public` (user photos), kept off-server. One more cron line:

```
30 1 * * * mysqldump -u USER -pPASS ulam_db | gzip > /backups/ulam-$(date +\%u).sql.gz
```

(`%u` = weekday number → 7 rotating daily backups.) Also back up `.env` (APP_KEY!) somewhere safe once.

---

## 7. Daily Weather Notification

Sends each user one push notification a day about the weather for their own saved location ("good day for the market, sunny!", "bring an umbrella before lunch", "rainy for the next few days, plan ahead"). **Costs nothing in Anthropic spend** — no AI/LLM call anywhere in this feature. Two independent pieces work together:

**1. Forecast — `App\Services\WeatherService`**
- Calls **Open-Meteo** (`api.open-meteo.com`, free, no API key) for a 7-day forecast (`weathercode` + `precipitation_probability_max`) at the user's exact saved lat/long.
- `classify()` maps that forecast to one of five fixed categories using hardcoded thresholds (never admin-edited, by design — this is the one part of the feature that should behave identically every day): `sunny`, `cloudy`, `light_rain`, `heavy_rain`, or `extended_rain` (3+ consecutive rainy days starting today — the only category that can show the Premium upsell).
- **Cached per location "bucket," not per user** — `bucketKey()` rounds lat/long to ~2 decimals (~1km), and `weather_forecast_cache` stores one row per bucket per day. Users in the same city/barangay share one Open-Meteo call instead of one each — keeps this within the free API's rate limits regardless of user count.

**2. Phrasing — `App\Services\WeatherPhraseSelector` + `weather_phrases` table**
- The actual message text is 100% admin-written, edited at Admin → Content → Weather Phrases (tabbed by category, ~15 starter "info" phrases per category seeded). The code only *picks* one, it never generates wording.
- Each phrase has a `variant_type`: `info` (the default), `meal_promo` (spotlights the current top-rated published recipe — highest `average_rating` then `vote_up_count` — via `{{recipe_name}}`/`{{recipe_author}}`/`{{rating}}`/`{{thumbs_count}}` tokens), or `premium_promo` (only offered for `extended_rain`, only to non-Premium users — "plan your meals ahead" nudge tying into 7-Day Advance Planning).
- `selectStandard()` tries `meal_promo` first if a qualifying recipe exists, falls back to `info` if the admin hasn't written a `meal_promo` phrase for that category yet. Token fill is plain `strtr()`, same substitution style as Email Templates.

**3. Delivery**
- `php artisan ulam:weather-daily` (scheduled daily at 6:00 AM in `routes/console.php`) groups all users with a saved location by bucket, resolves one message per bucket via the selector, and bulk-pushes via the existing `NotificationService::sendBulk()` — same pattern as `ulam:daily-reminders`.
- Tapping the notification opens the mobile app's `/weather-detail` screen, which calls `GET /weather/today` to fetch **today's** message fresh (recomputed on the fly from the cached forecast + current phrase pool, not replayed verbatim from what was originally pushed) — so if a user upgrades to Premium between the push firing and them tapping it, the detail screen reflects their current status correctly rather than a stale snapshot.
- `--dry-run` flag on the command logs what would be sent per bucket without writing or pushing anything — use this to sanity-check after editing phrases or seeding new markets/users.

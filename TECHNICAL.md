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
6. `php artisan config:cache && php artisan route:cache`  (re-run after every deploy).

### 2.2 CRON — scheduled jobs (REQUIRED in production)

Add **one** cron entry (cPanel → Cron Jobs, or `crontab -e`):

```
* * * * * cd /path/to/ulam && php artisan schedule:run >> /dev/null 2>&1
```

That single line ticks Laravel's scheduler every minute; Laravel itself decides
what actually runs (see `routes/console.php`):

| Command | Schedule | What it does |
|---|---|---|
| `ulam:maintenance` | hourly | Expires ended manual-GCash seller subscriptions (and re-syncs store visibility), expires ended boosts, sends "subscription ending in 3 days" reminders, prunes stale OTP codes |
| `billing:process-lifecycle` | hourly | PayMongo subscription grace/expiry/suspension |
| `ulam:daily-reminders` | 08:00 | Spending-log reminder notifications |
| `prices:refresh-ai` | 02:00 | AI market price refresh |
| `prices:refresh-gov` | 03:00 | DA Bantay Presyo / DTI SRP reference refresh |

**Without this cron line, none of the above ever runs.** Symptoms of a missing cron:
subscriptions never expire, no renewal reminders, stale government prices.

### 2.3 Queue worker (emails, image moderation)

Queued jobs (welcome emails, image moderation) need a worker. On shared hosting
without supervisor, the pragmatic option is a second cron line:

```
* * * * * cd /path/to/ulam && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

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
| `ANTHROPIC_API_KEY` | AI meal plans + AI price refresh. Kill switch without deploy: AppSettings `ai_meal_plans_enabled = 0`. |
| `RESEND_API_KEY` | Email (OTP codes, welcome). **Sandbox only delivers to cessto103@gmail.com until a domain is verified at resend.com/domains** — do that at hosting time, then set the from-address to the domain. |
| `PAYMONGO_SECRET_KEY` / `PAYMONGO_WEBHOOK_SECRET` | Placeholders until PayMongo goes live; webhook needs a public HTTPS URL (`/api/billing/webhooks/paymongo`). |
| `MAIL_FROM_ADDRESS` | Switch to the real domain address once Resend is verified. |

---

## 4. Security Runbook

- **Admin account:** `cessto103@gmail.com` is the sole `role=admin`. 2FA (Google Authenticator) is enrolled. **Pre-launch: change the password to a strong unique one** (Admin → Settings → Account, or the app's forgot-password flow).
- **2FA lockout recovery** (lost phone): from the server, `php artisan tinker` →
  `App\Models\User::where('email','cessto103@gmail.com')->update(['twofa_secret'=>null,'twofa_enabled_at'=>null,'twofa_last_ts'=>null]);` then re-enroll.
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

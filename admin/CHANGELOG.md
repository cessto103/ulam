# uLam Admin — Changelog

## v1.18.0 (2026-07-15)

### Added
- **About the App** (Content → About the App) — edit the title, body text, company name, and company link shown on the app's Settings → About the App screen, no app update needed.

## v1.17.1 (2026-07-15)

### Changed
- **Em dashes removed from all user-facing copy** in the admin panel (page descriptions, toasts, dialogs, card titles), replaced with a colon, comma, parenthesis, or a new sentence depending on context.

## v1.17.0 (2026-07-15)

Theme presets: switchable, named color/photo sets instead of one global config.

### Added
- **Preset gallery on the Theme page** — the header/dashboard/Awards sections you could already customize now live inside named, switchable presets. Create a new preset, duplicate an existing one, rename, activate ("go live" — only one preset is active in the app at a time), or delete. Seeded with 5 to start: **Default**, **Christmas**, **New Year**, **Araw ng Kalayaan**, and **Valentine's Day** — each a ready-made color set built from the same Primary/Secondary/Accent/Text roles as the original brand palette, so they slot into every themed section without touching a photo (upload one per preset anytime for the full seasonal look).

### Changed
- Theme section editors (image upload, focal point, fit, overlay colors) now edit whichever preset is selected in the gallery, not a single global config.

### Added
- **Theme page: Premium subscription header card** — the uLam Premium (Upgrade) screen header joins the header/dashboard/hero sections already controllable from Content → Theme.

## v1.16.0 (2026-07-15)

### Added
- **Premium features editor** (Monetization → "uLam Premium — included features") — edit the "Included in Premium" list shown on the app's Upgrade screen (titles, descriptions, emoji, free/premium flag) without an app release. Add/remove rows, reset to the built-in list anytime.

## v1.15.1 (2026-07-15)

### Added
- **Theme page: Profile hero header card** — Profile and Awards & Achievements pages share a photo+gradient hero header, forgotten from the initial Theme rollout; now controllable from Content → Theme like the other sections.

## v1.15.0 (2026-07-15)

### Added
- **Theme** (Content → Theme) — control the background photo (with a 9-point focal-point picker and cover/contain fit) and color overlay for the page header, the 5 Home dashboard boxes, and the 4 Awards stat boxes, with upload/reset per section. Falls back to the built-in look until a section is configured.
- **Recipe Comments** (Recipes → Recipe Comments) — moderate comments left on recipes: search, view, delete.

## v1.14.0 (2026-07-14)

### Added
- **Branding** (Content → Branding) — upload a replacement app logo (two slots: light backgrounds + white-on-terracotta) that the mobile app picks up everywhere without an app update; reset to the built-in uLam logo anytime. PNG/JPG/WebP up to 2 MB.

## v1.13.1 (2026-07-14)

### Added
- **Technical Guide** (Content → Technical Guide) — the operations reference (cron setup, deploy checklist, env vars, security runbook, EAS/FCM instructions, backups) rendered live inside the dashboard from the repo's `TECHNICAL.md`, so the docs are always in the same place you do the work. Single source of truth: edit the file, the page updates.

## v1.13.0 (2026-07-14)

Content Management: legal documents as a versioned mini-CMS.

### Added
- **Legal Documents** (`/legal`, sidebar under Content) — manage Terms & Conditions and the Privacy Policy without code changes:
  - Markdown **draft editor with debounced autosave**, edit/preview toggle, version field, and a required "What's changed" changelog;
  - **Version history table** with status filter and search; per-version actions: view, restore-as-draft, duplicate, publish, delete draft;
  - **Publish workflow** — publishing archives the previous live version in the same transaction (exactly one live version per document, enforced server-side) and triggers the mobile app's mandatory re-acceptance prompt;
  - Live-version summary cards: current version, user acceptance count, and the public page URL (`/legal/terms`, `/legal/privacy`) for the Play Store listing.
- Seeded professionally written v1.0.0 content for both documents, tailored to uLam (information platform, not a delivery service; PH Data Privacy Act; 48-hour subscription refund policy).

### Notes
- Statuses implemented: draft / published / archived. "Pending review / approved" from the original spec were deliberately skipped for a single-admin team — the status column is a string, so they can be added later without schema changes.
- Content is Markdown (not a WYSIWYG rich-text editor) — versionable, diffable, and renders consistently across the admin preview, the public web page, and the mobile app.

## v1.12.0 (2026-07-14)

Dashboard lockdown: one door, with a second lock.

### Added
- **Two-factor authentication (Google Authenticator/TOTP)** — new Settings → Security page: scan a QR (or enter the key manually), confirm a code, and from then on admin sign-in requires password **plus** a fresh 6-digit code. The sign-in form shows the code field automatically when the server demands it. Codes are one-time (replay of a just-used code is rejected server-side), the secret is stored encrypted, and disabling 2FA requires both the password and a current code.

### Removed
- **The Filament admin panel** (backend) — an unmaintained second dashboard whose login page was publicly served at `/admin`. The React SPA is now the only admin surface.

## v1.11.0 (2026-07-13)

Admin coverage for the app's new booster and store-moderation features — both previously backend-only.

### Added
- **Boost Review** (`/boosts`) — approval queue for manual-GCash recipe/store boost payments, mirroring the existing seller-subscriptions review pattern: pending/active counts, status filter, search by reference/user, Approve and Reject (with a required reason) actions.
- **Store Comments & Ratings** (`/tindahan-comments`) — moderation screen for the app's new store rating/comment feature, tabbed between Comments (search + delete) and Ratings (view + remove, which recalculates the store's average automatically).

### Notes
- `AdminBoostController::index` now eager-loads and resolves the boosted recipe/store's name into the response — it previously only returned the raw polymorphic type/id.
- Both screens follow the lighter single-file pattern (like Seller Subscriptions) rather than the full CRUD-scaffold pattern (like Tindahan/Comments) — neither needs create/edit, only review actions.

## v1.10.0 (2026-07-12)

Post-audit reconciliation of the manual-GCash / PayMongo fork, plus a real refund workflow.

### Added
- **Refund button on the Payments page** — shows per row when a payment is a paid PayMongo transaction; opens a dialog (amount pre-filled to the full amount, editable for partial refunds; reason dropdown matching PayMongo's accepted reasons) and calls the existing refund endpoint. Previously that endpoint had no admin UI at all.
- **`BillingSimulationSeeder`** (`php artisan db:seed --class=BillingSimulationSeeder`) — seeds a full set of demo subscriptions, checkout sessions, payments, webhook events, and boosts covering every state the dashboards can show: active, grace period, expired, superseded (plan upgrade), cancel-at-period-end, a failed checkout, and a refunded subscription. Idempotent — safe to re-run.

### Fixed
- **`BillingService::refund()` had two correctness gaps**, found during a full audit of the PayMongo billing platform: (1) issuing a refund never marked the underlying `Payment` as refunded, so it kept counting toward revenue and could be refunded a second time; (2) refunding a subscription payment never actually ended the subscription — the seller kept full access until the period naturally expired, contradicting the confirmed policy that a refund ends access immediately. Both are now fixed: a full refund marks the payment `refunded`/`partially_refunded`, and — for full refunds — ends the subscription on the spot and re-syncs store visibility.
- Payments table: colored status badges for `refunded`/`partially_refunded`/`failed`; schema updated to carry `refunded_at`/`failure_code`.

### Notes
- **Manual-GCash backend left dormant, on purpose.** The Seller Subscriptions approve/reject/refund queue, `/seller/*` routes, and the `ad_subscriptions`-based flow still exist in full but are unlinked from any UI — kept as a zero-cost fallback, not deleted.
- The PayMongo path still cannot process a real payment: `.env`'s `PAYMONGO_SECRET_KEY` is a placeholder and there is no public hosting for the webhook yet. Both are prerequisites, independent of anything in this release.

## v1.9.0 (2026-07-12)

- Replaced manual GCash approval as the primary path with server-created PayMongo Checkout.
- Added subscription health metrics, lifecycle states, and a webhook failure log.
- Billing activation is webhook-authoritative and idempotent; redirects cannot unlock features.
- Manual-payment records remain available for migration and historical reconciliation.

## v1.1.0 (2026-07-12)

Phase 1 of seller monetization: manual GCash subscriptions, editable pricing, and the support desk.

### Added
- **Seller Subscriptions page** (`/seller-subscriptions`) — pending-payment approval queue with counts badge. Approve (verify GCash reference + exact amount first), reject with a reason, and refund active subscriptions (send-back happens in GCash first; access ends immediately). Search by user or reference number; Pending / Active / All tabs.
- **Plans & Pricing page** (`/monetization`) — edit every tier's store/item limits (including the Free tier's caps) and all duration prices (7d/15d/monthly/yearly); boost price list (sellable in Phase 3); **GCash payment settings** (number, account name, instructions) with a **payments kill switch** that hides checkout in the app instantly.
- **Support Tickets page** (`/support-tickets`) — inbox with Open/Answered/Closed tabs, chat-style thread view, replies notify the user in-app (push + notification), close ticket.
- **FAQs page** (`/faqs`) — manage the app's Help & Support Q&A in English + Tagalog with categories, sort order, and draft/published state.
- Sidebar: new **Monetization** and **Support** groups.

### Changed
- Payments ledger now includes manual GCash seller-subscription payments and negative refund rows; "PayMongo Ref" column renamed to "Reference".

## v1.0.0 (2026-07-12)

First uLam-branded release (changelog restarted; template history below).

### Added
- **Filipino Food Palette theme** — light mode on warm cream `#FFF8E8` with charcoal text, terracotta `#C45E3A` primary (buttons), leaf-green secondary, gold accents, warm borders; new warm-charcoal dark mode. Chart palette re-anchored to leaf/gold/terracotta while keeping cross-hue picks for distinguishability.
- **Official uLam logo** — hand-lettered mark (generated from `uLam-app/assets/ulam-logo.svg`) on the sign-in page, in the sidebar chip, and as SVG favicons (light variant for dark browser UIs).
- Sidebar branding: "uLam Admin · Owner Dashboard" (was "Shadcn-Admin · Vite + ShadcnUI").

---

# Template history (shadcn-admin)

## v2.2.1 (2025-11-06)

### Fix

- **style**: update data attribute class in authenticated layout (#249)
- prevent navigation to 500 page during development (#240)
- **style**: apply variant 'destructive' to sign-out buttons (#236)
- add missing space in profile form (#235)

### Refactor

- enhance tables and update table layout (#234)

## v2.2.0 (2025-10-09)

### Feat

- add analytics tab in dashboard page (#220)
- add extra AppTitle component for sidebar header (#216)
- update 2-column sign in page (#213)

### Fix

- update sidebar menu chevron direction in RTL mode (#229)
- pagination button spacing (#215)
- upgrade lucide-react to solve antivirus warning (#211)

### Refactor

- move sidebar related components into app-sidebar
- change SidebarInset component from 'main' to 'div'
- replace extra main container query with content container query
- replace inline svg logo with logo component (#214)

## v2.1.0 (2025-08-23)

### Feat

- enhance data table pagination with page numbers (#207)
- enhance auth flow with sign-out dialogs and redirect functionality (#206)

### Refactor

- reorganize utility files into `lib/` folder (#209)
- extract data-table components and reorganize structure (#208)

## v2.0.0 (2025-08-16)

### BREAKING CHANGE

- CSS file structure has been reorganized

### Feat

- add search param sync in apps route (#200)
- improve tables and sync table states with search param (#199)
- add data table bulk action toolbar (#196)
- add config drawer and update overall layout (#186)
- RTL support (#179)

### Fix

- adjust layout styles in search and top nav in dashboard page
- update spacing and layout styles
- update faceted icon color
- improve user table hover & selected styles (#195)
- add max-width for large screens to improve responsiveness (#194)
- adjust chat border radius for better responsiveness (#193)
- update hard-coded or inconsistent colors (#191)
- use variable for inset layout height calculation
- faded-bottom overflow issue in inset layout
- hide unnecessary configs on mobile (#189)
- adjust file input text vertical alignment (#188)

### Refactor

- enforce consistency and code quality (#198)
- improve code quality and consistency (#197)
- update error routes (#192)
- remove DirSwitch component and its usage in Tasks (#190)
- standardize using cookie as persist state (#187)
- separate CSS into modular theme and base styles (#185)
- replace tabler icons with lucide icons (#183)

## v1.4.2 (2025-07-23)

### Fix

- remove unnecessary transitions in table (#176)
- overflow background in tables (#175)

## v1.4.1 (2025-06-25)

### Fix

- user list overflow in chat (#160)
- prevent showing collapsed menu on mobile (#155)
- white background select dropdown in dark mode (#149)

### Refactor

- update font config guide in fonts.ts (#164)

## v1.4.0 (2025-05-25)

### Feat

- **clerk**: add Clerk for auth and protected route (#146)

### Fix

- add an indicator for nested pages in search (#147)
- update faded-bottom color with css variable (#139)

## v1.3.0 (2025-04-16)

### Fix

- replace custom otp with input-otp component (#131)
- disable layout animation on mobile (#130)
- upgrade react-day-picker and update calendar component (#129)

### Others

- upgrade Tailwind CSS to v4 (#125)
- upgrade dependencies (#128)
- configure automatic code-splitting (#127)

## v1.2.0 (2025-04-12)

### Feat

- add loading indicator during page transitions (#119)
- add light favicons and theme-based switching (#112)
- add new chat dialog in chats page (#90)

### Fix

- add fallback font for fontFamily (#110)
- broken focus behavior in add user dialog (#113)

## v1.1.0 (2025-01-30)

### Feat

- allow changing font family in setting

### Fix

- update sidebar color in dark mode for consistent look (#87)
- use overflow-clip in table paginations (#86)
- **style**: update global scrollbar style (#82)
- toolbar filter placeholder typo in user table (#76)

## v1.0.3 (2024-12-28)

### Fix

- add gap between buttons in import task dialog (#70)
- hide button sort if column cannot be hidden & update filterFn (#69)
- nav links added in profile dropdown (#68)

### Refactor

- optimize states in users/tasks context (#71)

## v1.0.2 (2024-12-25)

### Fix

- update overall layout due to scroll-lock bug (#66)

### Refactor

- analyze and remove unused files/exports with knip (#67)

## v1.0.1 (2024-12-14)

### Fix

- merge two button components into one (#60)
- loading all tabler-icon chunks in dev mode (#59)
- display menu dropdown when sidebar collapsed (#58)
- update spacing & alignment in dialogs/drawers
- update border & transition of sticky columns in user table
- update heading alignment to left in user dialogs
- add height and scroll area in user mutation dialogs
- update `/dashboard` route to just `/`
- **build**: replace require with import in tailwind.config.js

### Refactor

- remove unnecessary layout-backup file

## v1.0.0 (2024-12-09)

### BREAKING CHANGE

- Restructured the entire folder
hierarchy to adopt a feature-based structure. This
change improves code modularity and maintainability
but introduces breaking changes.

### Feat

- implement task dialogs
- implement user invite dialog
- implement users CRUD
- implement global command/search
- implement custom sidebar trigger
- implement coming-soon page

### Fix

- uncontrolled issue in account setting
- card layout issue in app integrations page
- remove form reset logic from useEffect in task import
- update JSX types due to react 19
- prevent card stretch in filtered app layout
- layout wrap issue in tasks page on mobile
- update user column hover and selected colors
- add setTimeout in user dialog closing
- layout shift issue in dropdown modal
- z-axis overflow issue in header
- stretch search bar only in mobile
- language dropdown issue in account setting
- update overflow contents with scroll area

### Refactor

- update layouts and extract common layout
- reorganize project to feature-based structure

## v1.0.0-beta.5 (2024-11-11)

### Feat

- add multiple language support (#37)

### Fix

- ensure site syncs with system theme changes (#49)
- recent sales responsive on ipad view (#40)

## v1.0.0-beta.4 (2024-09-22)

### Feat

- upgrade theme button to theme dropdown (#33)
- **a11y**: add "Skip to Main" button to improve keyboard navigation (#27)

### Fix

- optimize onComplete/onIncomplete invocation (#32)
- solve asChild attribute issue in custom button (#31)
- improve custom Button component (#28)

## v1.0.0-beta.3 (2024-08-25)

### Feat

- implement chat page (#21)
- add 401 error page (#12)
- implement apps page
- add otp page

### Fix

- prevent focus zoom on mobile devices (#20)
- resolve eslint script issue (#18)
- **a11y**: update default aria-label of each pin-input
- resolve OTP paste issue in multi-digit pin-input
- update layouts and solve overflow issues (#11)
- sync pin inputs programmatically

## v1.0.0-beta.2 (2024-03-18)

### Feat

- implement custom pin-input component (#2)

## v1.0.0-beta.1 (2024-02-08)

### Feat

- update theme-color meta tag when theme is updated
- add coming soon page in broken pages
- implement tasks table and page
- add remaining settings pages
- add example error page for settings
- update general error page to be more flexible
- implement settings layout and settings profile page
- add error pages
- add password-input custom component
- add sign-up page
- add forgot-password page
- add box sign in page
- add email + password sign in page
- make sidebar responsive and accessible
- add tailwind prettier plugin
- make sidebar collapsed state in local storage
- add check current active nav hook
- add loader component ui
- update dropdown nav by default if child is active
- add main-panel in dashboard
- **ui**: add dark mode
- **ui**: implement side nav ui

### Fix

- update incorrect overflow side nav height
- exclude shadcn components from linting and remove unused props
- solve text overflow issue when nav text is long
- replace nav with dropdown in mobile topnav
- make sidebar scrollable when overflow
- update nav link keys
- **ui**: update label style

### Refactor

- move password-input component into custom component dir
- add custom button component
- extract redundant codes into layout component
- update react-router to use new api for routing
- update main panel layout
- update major layouts and styling
- update main panel to be responsive
- update sidebar collapsed state to false in mobile
- update sidebar logo and title
- **ui**: remove unnecessary spacing
- remove unused files

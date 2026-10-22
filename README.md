# Assanio (working title) — Multi-Tenant Platform v1

Shopify-style foundation for our restaurant-template SaaS: owners sign up,
create a store from one of the 4 finished templates, and every store's
orders flow into **one central dashboard**.

Architecture: see **PLATFORM_BLUEPRINT.md**. The product name is a working
title — rename it in ONE place: `PLATFORM_NAME` in `config.php`.

## Run locally

```bash
cd ~/workspace/restaurant-saas/platform
php -S 127.0.0.1:8890
# open http://127.0.0.1:8890/
```

Requirements: PHP 8+ with `pdo_sqlite` and `curl` (standard on Hostinger
shared hosting; locally: `apt install php-sqlite3 php-curl`). The SQLite
database is created automatically at `data/platform.sqlite` on first use.
Generated store websites appear under `stores/<slug>/` and are browsable at
`http://127.0.0.1:8890/stores/<slug>/`.

## Verify it works (self-test)

```bash
php -S 127.0.0.1:8890        # in one terminal
bash selftest.sh              # in another
```

`selftest.sh` runs the full owner journey with curl — register → login →
create a desi-bbq store → verify generated folder + seeded `content.json` →
post an order to the central API (valid key accepted, wrong key = 401) →
order visible in dashboard → status change persists → a checkout on the
generated store's own site forwards a copy centrally → logout — then
**cleans up after itself** (test user/store/orders removed). Expected:
`15 passed, 0 failed`. Latest run (2026-10-20): **15/15 PASS**.

`bash selftest2.sh` runs the **phase-2** journey — 2 owners → one promoted
to super-admin via `tools/make_super.php` → SSO "Edit Website" flow (token →
store-admin session → protected action, **no store password**) → token
reuse + expired token rejected → order walked to *delivered* → revenue card
math → checkout-mode write lands in the store's `content.json` →
super-admin plan controls → suspend (storefront unavailable) → activate —
then cleans up. Expected: `26 passed, 0 failed`. Latest run (2026-10-20):
**26/26 PASS** (plus a headless-Chromium check that a suspended storefront
really renders the unavailable screen).

## What works (v1)

- **Auth** — register / login / logout, bcrypt (`password_hash`),
  session regeneration on login, CSRF tokens on all forms.
- **Dashboard** — "My Restaurants" with per-store order counts + "new" badges.
- **Create Store wizard** — name, slug (auto + unique), template (4 cards),
  WhatsApp, city. On submit it:
  1. creates the store row + a per-store API key,
  2. copies the chosen template into `stores/<slug>/`,
  3. seeds `content.json` with the store's name, WhatsApp (normalized to
     intl format) and city,
  4. writes `platform.json` and patches the store's `api/order.php` to
     forward a copy of every order to the central API (best-effort, 3s
     timeout — a platform outage never blocks a checkout).
- **Central Orders API** — `POST api/order.php` with header
  `X-Store-Key: <key>`; accepts the exact payload shape the templates
  already send; returns `{ ok:true, id:"ORD-XXXXXX" }`; wrong/missing
  key → 401.
- **Orders dashboard** — all stores, newest first, store filter, coupon /
  discount breakdown, status buttons (Accept / Preparing / Delivered /
  Cancel), order detail page. Owners only ever see their own stores' orders.
- **Store settings** — edit name/WhatsApp/city, view + regenerate the API
  key (regeneration also updates the generated site's `platform.json`),
  regenerate website files, links to the live site and its store admin.
- **One-login editing (phase 2)** — "Edit Website" on the dashboard /
  settings mints a one-time SSO token (SHA-256 hash stored, ≤2 min expiry,
  single use, bound to that store) and signs the owner straight into the
  store's Admin 2.0 — no separate store password. At generation time the
  platform drops `admin/platform_sso.php` into the store and patches the
  copied `admin/save.php` login action to accept the token; a missing patch
  anchor fails generation loudly (see `data/generation.log`).
- **Super-admin (phase 2)** — `users.role` is `owner` | `super`. Promote a
  registered user with `php tools/make_super.php <email>` (no accounts are
  seeded in code). `/admin.php` lists all owners, stores and orders
  (cross-owner) and gives per-store controls: plan tier
  (starter/business/pro), payment status (paid/unpaid), renewal date, and
  Suspend / Activate.
- **Store suspension (phase 2)** — suspending writes
  `platform_status.json` (`{"status":"suspended"}`) into the store folder;
  the generated storefront checks it on load and swaps in a polite
  "temporarily unavailable" screen. Activating removes the file. Storefronts
  generated before phase 2 need a "Regenerate website files" to gain the
  check.
- **Dashboard completion (phase 2)** — stat cards (this month's revenue =
  DELIVERED order totals, new orders waiting, total orders) and a
  recent-orders widget (latest 5 across stores, status pills, links to the
  order pages).
- **Checkout mode (phase 2)** — Store Settings → "Checkout mode"
  (WhatsApp / Website / Both) reads and writes the store's
  `content.json` key `site.checkoutMode` — the same key the templates use.

## What's stubbed / next pass

- **Online payment** — v2 (Safepay/PayFast: JazzCash/Easypaisa/cards).
  v1 is Cash on Delivery, like most PK food ordering.
- **Custom domains** — v1.5; v1 stores live under `stores/<slug>/`.
- **API keys are stored unhashed** so owners can view them on the settings
  page (documented trade-off, blueprint §3); v1.5 moves to hash + show-once.
- Orders are capped at the latest 200 in the list view (fine for v1).
- Status changes are plain POST forms (no AJAX) — deliberate, matches the
  templates' no-build-step vanilla style.

## Credentials

None seeded. Owners self-register. Note the **generated store sites keep
their template admin** at `stores/<slug>/admin/` with the template's
default password (documented in each template's README) — **change it
during customer go-live**, it's on the launch checklist.

## Layout

```
config.php            PLATFORM_NAME + template catalogue (single rename point)
lib.php               DB, auth, CSRF, helpers, generateStoreFiles()
index.php             landing stub
register/login/logout.php
dashboard.php         My Restaurants (+ revenue card, recent orders)
create-store.php      store wizard (+ file generation)
edit-website.php      one-login editing: mints SSO token → store admin
store-settings.php    details, API key, checkout mode, regeneration
orders.php / order.php  orders dashboard + detail
admin.php             super-admin panel (owners, stores, orders, suspend)
tools/make_super.php  CLI: promote a registered user to super-admin
api/order.php         central orders API (X-Store-Key)
assets/style.css      platform styling (no external assets)
data/                 platform.sqlite (auto-created; .htaccess deny-all)
stores/<slug>/        generated store websites
selftest.sh           v1 curl journey test (self-cleaning)
selftest2.sh          phase-2 curl journey test (self-cleaning)
```

Per-store generation also writes two phase-2 artifacts into each store:
`admin/platform_sso.php` (SSO token validator) and a suspension-check
snippet inside the store's `index.html`; suspension state lives in the
store-root `platform_status.json` (present only while suspended).

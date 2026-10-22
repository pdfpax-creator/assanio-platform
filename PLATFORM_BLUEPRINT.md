# Assanio — Architecture Blueprint

> **"Assanio" is a working title.** The public name lives in ONE place:
> `PLATFORM_NAME` in `config.php`. Renaming the product = one line.

## 1. What this is

Today each restaurant is a **standalone copy** of one of our 4 templates
(`templates/<template>/` → folder with `index.html`, `content.json`, `admin/`,
`api/order.php`). The restaurant's customers order on the site; the order is
saved in that site's own `api/orders.json` and mirrored to the owner on
WhatsApp.

The platform (Shopify model) turns that into **one central system**:

```
Owner signs up ──► creates a Store (picks 1 of 4 templates)
                        │
                        ▼
             Store site GENERATED (folder per store slug)
             stores/<slug>/  ← full template copy, seeded content.json
                        │
Customer orders on store site
                        │
                        ├─► saved locally (store's api/orders.json, as today)
                        └─► POST copy ──► PLATFORM central orders API
                                        (per-store API key)
                                                │
                                                ▼
                              Owner's CENTRAL dashboard
                    all stores · status flow · new-order badge
```

## 2. Components

| Component | File(s) | Notes |
|---|---|---|
| Config | `config.php` | `PLATFORM_NAME`, paths, template catalogue. Single rename point. |
| Core library | `lib.php` | SQLite connection + schema bootstrap, auth helpers, CSRF, escaping, slug/phone helpers, `generateStoreFiles()`. |
| Landing | `index.php` | Public stub: pitch + Log in / Create your restaurant. |
| Auth | `register.php`, `login.php`, `logout.php` | `password_hash`/`password_verify`, session auth, `session_regenerate_id(true)` on login. |
| Dashboard | `dashboard.php` | "My Restaurants" — owner's stores + new-order badge counts, revenue stat cards, recent-orders widget. |
| Create Store | `create-store.php` | One-page wizard: name, slug (auto, unique), template (4 cards), WhatsApp, city. |
| Edit Website (SSO) | `edit-website.php` | Mints a one-time SSO token and redirects the owner into the store's Admin 2.0 (see §8). |
| Store settings | `store-settings.php` | Slug, template, API key (view + regenerate), WhatsApp, checkout mode, link to store folder. |
| Orders dashboard | `orders.php`, `order.php` | Cross-store order list (newest first, store filter), status buttons, detail view. |
| Super-admin | `admin.php`, `tools/make_super.php` | All owners/stores/orders + plan, payment, renewal, suspend/activate. CLI promotes users to `super`. |
| Central Orders API | `api/order.php` | JSON POST + `X-Store-Key` header → stores order centrally. |
| Data | `data/platform.sqlite` | SQLite via PDO. Deny-all `.htaccess` ships in `data/` for Apache/Hostinger. |
| Generated stores | `stores/<slug>/` | One folder per store (see §4). |

Stack: **PHP 8 + SQLite (PDO), zero frameworks, zero build step.** Runs on
`php -S` locally, deploys to Hostinger shared hosting as plain files.

## 3. Database schema (SQLite)

```sql
users   (id INTEGER PK, name, email UNIQUE, password_hash, created_at,
         role DEFAULT 'owner')            -- 'owner' | 'super' (phase 2)
stores  (id INTEGER PK, owner_id → users.id, name, slug UNIQUE,
         template, whatsapp, city, api_key, created_at,
         plan DEFAULT 'starter',          -- starter | business | pro
         payment_status DEFAULT 'unpaid', -- paid | unpaid      (phase 2)
         renewal_date DEFAULT '',         -- YYYY-MM-DD
         status DEFAULT 'active')         -- active | suspended
orders  (id TEXT PK  -- 'ORD-XXXXXX', same id format as the templates
         store_id → stores.id, status DEFAULT 'new',
         customer_name, customer_phone, customer_address, customer_notes,
         items_json, subtotal, discount, coupon, total, created_at)
sso_tokens (id INTEGER PK, token_hash UNIQUE /* SHA-256 of the raw token */,
         user_id → users.id, store_id → stores.id,
         expires_at /* unix ts, +120 s */, used_at /* NULL until consumed */,
         created_at)                                        -- (phase 2)
```

- All queries are prepared statements. All HTML output is escaped (`e()`).
- All state-changing forms carry a CSRF token.
- **API keys** are 48-hex-char random values, one per store, shown to the
  owner on the settings page (needed so they can wire/verify integrations)
  and regenerable. *Trade-off (v1):* keys are stored in the DB as-is, like a
  config token, not hashed — because the owner must be able to view the key.
  v1.5 hardening option: hash keys + show-once-at-creation (see roadmap).

## 4. How a store's site is published (v1)

`generateStoreFiles($store)` in `lib.php`:

1. Recursively copy `templates/<template>/` → `stores/<slug>/`.
2. Reset the copy's `api/orders.json` to `[]` (no demo orders leak).
3. Patch the copy's `content.json`:
   - `site.name` → the restaurant's name
   - `site.whatsappDigits` / `site.phoneDigits` → owner's WhatsApp (normalized to `92…` intl format), `site.phone` → display format
   - `site.coverage` / `site.mapQuery` → the city
   - `site._demoNote` → platform provenance note
4. Write `stores/<slug>/platform.json` — `{ store slug, central API URL, api key }`.
5. Patch the copy's `api/order.php`: after its local save succeeds, it makes a
   **best-effort fire-and-forget cURL POST** of the same payload to the
   platform API with its `X-Store-Key` (3 s timeout; a platform outage never
   blocks a customer's checkout — the local copy + WhatsApp still work).
6. Phase-2 patches (anchors verified *before* the copy — see §8/§9): write
   `admin/platform_sso.php`, patch the copied `admin/save.php` login action
   for SSO tokens, inject the suspension-check snippet into `index.html`,
   and re-write `platform_status.json` if the store is currently suspended.

On Hostinger this maps to `stores/<slug>/` being served as a plain folder;
v1.5 gives each store its own subdomain/custom domain via Hostinger
subdomain → folder mapping.

> The generated store keeps its own template admin (`/admin/`, template
> default password). Changing that default password at setup is on the
> go-live checklist (README).

## 5. Central orders flow

1. Customer checks out on the store site (existing template flow, unchanged).
2. Store's `api/order.php` saves locally (as today) and forwards a copy:
   `POST {platform}/api/order.php` with header `X-Store-Key: <key>` and the
   **same JSON payload shape** the templates already use
   (`customer{name,phone,address,notes}`, `items[{name,qty,price}]`,
   `subtotal`, `discount`, `coupon`, `total`).
3. Platform validates the key (401 on mismatch), validates the payload with
   the same rules as the template endpoint, inserts a row linked to the
   store, returns `{ ok:true, id:"ORD-XXXXXX" }`.
4. Owner sees the order in **Orders** (all stores, newest first, store
   filter) and on the dashboard badge ("N new").

### Order status flow

```
new ──► accepted ──► preparing ──► delivered
  └──────────────► cancelled ◄──────────────┘ (any stage)
```

Status changes are POST forms on `orders.php` / `order.php` (CSRF-protected,
owner-scoped: an owner can only touch orders of their own stores).

## 6. Roadmap

- **v1** — auth, store creation + file generation, central orders API,
  orders dashboard with status flow, store settings + key regeneration.
  Payments: **Cash on Delivery** (order online, pay on delivery — the PK
  norm). WhatsApp remains the instant notification.
- **Phase 2 (done)** — one-login editing via SSO tokens (§8), super-admin
  panel (owners/stores/orders + plan/payment/renewal + suspend), store
  suspension (§9), dashboard revenue card + recent orders, per-store
  checkout mode (`site.checkoutMode`: whatsapp / web / both),
  landing pricing section (Starter Rs 1,999 / Business Rs 3,499 / Funnel Pro
  Rs 5,999; yearly = 10× monthly; setup Rs 9,999 waived for first 5 pilot
  customers), and **order source tracking** (§10): the store's
  `api/order.php` sends `source:"website"|"whatsapp"`; the central API
  validates it (only exact 'website' counts, everything else → 'whatsapp')
  into an `orders.source` column (idempotent migration; legacy rows default
  to 'whatsapp'); chips ("Website Direct" / "WhatsApp") render on the
  orders table + order detail drawer, with an All/Website/WhatsApp source
  filter.
- **v1.5** — custom domains per store (owner's own domain via CNAME +
  Hostinger mapping), hashed API keys with show-once creation.
- **v2** — online payment via **Safepay / PayFast** (JazzCash + Easypaisa +
  cards), owner connects their own gateway account; customer
  order-tracking page; platform billing/subscriptions for owners.

## 7. Security baseline (implemented)

`password_hash` (bcrypt) · session auth + `session_regenerate_id` on login ·
prepared statements everywhere · CSRF tokens on every POST form · escaped
output · API key required for the orders API (401 otherwise) · per-owner
data scoping on every query · super-admin routes guarded by `users.role` ·
SSO tokens: random 32-byte, only SHA-256 stored, ≤120 s expiry, single-use
atomic consume, bound to one store · `data/` blocked by `.htaccess` on
Apache.

## 8. One-login editing (SSO) — how it works

The owner should never need the store's separate template-admin password.

1. Owner clicks **Edit Website** (`edit-website.php?id=N`, owner-scoped).
2. Platform inserts an `sso_tokens` row: SHA-256 hash of a random 32-byte
   token, the owner's id, the store's id, `expires_at = now + 120 s`.
3. The browser is redirected to the store's own endpoint
   `stores/<slug>/admin/platform_sso.php?t=<raw token>`.
4. That file is **generated, not part of the template**: at store-generation
   time the platform writes it into the store's `admin/` (self-contained;
   the platform DB's relative path and the store slug are baked in) and
   patches the copied `admin/save.php` so its `login` action also accepts
   `sso_token` via the same validator (`rp_sso_consume()`).
5. Validation is a single atomic
   `UPDATE sso_tokens SET used_at = now WHERE token_hash = ? AND used_at IS NULL AND expires_at >= now AND store_id = (this store)` —
   exactly one request can ever succeed per token (single-use), a token for
   store A is useless at store B, and after 2 minutes it is dead.
6. On success the endpoint starts the store-admin session
   (`$_SESSION[CH_SESSION_KEY] = true`, the same key the template admin's
   password login sets) and redirects to the admin UI. No store password is
   transmitted, stored or displayed anywhere in this flow.
7. **Loud failure by design:** generation verifies the patch anchors
   (`if ($action === 'login') {` in the template's `admin/save.php`,
   `</body>` in `index.html`) *before* copying; a missing anchor aborts
   generation with the reason written to `data/generation.log` and shown to
   the user — a store is never silently shipped without SSO.

## 9. Store suspension — how it works

- Super-admin (or `set_store_suspended()` in `lib.php`) flips
  `stores.status` and writes `stores/<slug>/platform_status.json` =
  `{"status":"suspended"}` (activation deletes the file).
- Every generated storefront carries a tiny injected snippet (before
  `</body>`, marker comment `Assanio suspension check`) that fetches
  `platform_status.json` with `cache: no-store`. Absent file / any error →
  the store renders normally. If suspended, the snippet replaces the page
  body with a polite unavailable screen ("This restaurant is temporarily
  unavailable — please check back soon." + the platform name — no admin or
  billing details are exposed) and re-asserts it for a few seconds so the
  template's own renderer can't paint over it.
- Regenerating a suspended store re-writes the status file, so suspension
  survives regeneration. Stores are still *editable* while suspended —
  suspension only affects the public storefront.

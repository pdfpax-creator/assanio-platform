# Template 3 — Cafe ("The Quiet Cup" design)

Production restaurant website template. JSON-driven: all customer content lives in
`content.json`; the design never needs code edits.

**Look & feel:** light editorial cream (`#fff9ef`), near-black ink (`#181612`),
terracotta accent (`#8d4d35`). Playfair Display serif headings (regular/italic,
never heavy bold) + Inter body. Generous whitespace, white journal cards with
micro-borders — "a little pause in your day."

## Structure
- `index.html` — public site (fetches `content.json`, renders via vanilla JS)
- `content.json` — ALL editable content: site info, hero, about/story, deal of
  day, menu (categories + items), combo deals, how-ordering-works, gallery,
  reviews, delivery areas, FAQ, location, reservation, QR, WhatsApp circle,
  footer
- `images/` — local images (JPG, optimized, ~312KB total). No remote image
  dependencies.
- `admin/` — password-protected admin panel (`site.com/admin`)

## New customer setup (5 minutes)
1. Copy this folder to the customer's site directory.
2. Edit `content.json` (or use the admin panel) — name, phone, WhatsApp number,
   address, timings, menu items + PKR prices, photos.
3. **Change the admin password**: open `admin/config.php`, generate a new hash:
   `php -r "echo password_hash('NEW-PASSWORD', PASSWORD_BCRYPT), PHP_EOL;"`
   and replace the hash. Give the password to the customer at handover.
   Default password is `quietcup123` (change before going live).
   NOTE (Oct 2026): `site.phone` / `whatsappDigits` are currently set to the
   owner's DEMO number 03193722519 for testing — replace with the customer's
   real numbers at every customer setup.
4. WhatsApp ordering uses the `site.whatsappDigits` number in `content.json`
   (digits only, e.g. `923001234567`) with a prefilled order message.

## Admin panel
- Login at `/admin/` — sections: Orders, Site Info, Hero, Deal of the Day, Menu
  (add/edit/delete items per category), Combo Deals, Gallery, Reviews,
  Location, QR, Footer, Cart Text, **Our Story**, **How Ordering**,
  **Delivery Areas**, **FAQ**, **WhatsApp Deals**, **Social Links**,
  **Announcement Bar**, **Design & Fonts**.
- **Page Sections** (first card): Elementor-style show/hide toggles for all
  17 page sections. Header, cart and checkout always stay visible.
- **Social Links**: add/remove rows; platform dropdown
  (Facebook/Instagram/TikTok/YouTube/X) + profile URL. Empty URL = hidden.
- **Announcement Bar**: show/hide toggle, text, optional link + link text.
  Visitors can dismiss it (×); it returns on their next visit.
- **Design & Fonts**: heading/body font dropdowns (curated Google Fonts),
  primary + accent color pickers. Save, then refresh the site.
- Every image upload field shows its recommended size (e.g. Hero 1200x800).
- Uploads: JPG/PNG/WebP, max 2MB, stored in `images/` with safe filenames.
- Saves are atomic (temp file + rename) and session-protected.

## Cart + WhatsApp checkout (how the cafe receives orders)
1. Customer taps **ADD +** on menu items (or **Order Deal** on a combo) — the
   slide-in cart drawer opens immediately with the item inside (bag icon in the
   header, ORDER NOW button, or the sticky-bar WhatsApp button also opens it).
   Cart persists in localStorage.
2. In the drawer: qty steppers, subtotal, checkout form (name / phone /
   address required, notes optional; Pakistani mobile validated).
3. **Order via WhatsApp** → the order is POSTed to `api/order.php` (saved to
   `api/orders.json`) AND the customer's WhatsApp opens with the full order
   message addressed to the cafe's `site.whatsappDigits` number.
   WhatsApp always opens even if the server save fails — the chat message is
   the primary order channel, the JSON copy is the backup/inbox.
4. The cafe sees every order twice:
   - **WhatsApp chat**: `*New Order — {name}*` with item lines, total,
     customer name/phone/address/notes.
   - **Admin → Orders tab**: newest-first list with customer details, item
     lines, totals, New/Completed status pill, "Mark completed" and Delete.
     The tab shows a count badge of new orders.
5. All cart/checkout UI text lives in the `cart` section of `content.json`
   (editable in Admin → Cart Text) with built-in English fallbacks.

## Notes
- Reviews ship with a "sample data" disclaimer — replace with real reviews.
- QR section is a decorative placeholder; swap in a real QR (API/service)
  per customer at deploy time.
- Fonts: Google Fonts (Playfair Display 400/500/600 + italics for headings,
  Inter body). Headings are never set in heavy bold weights.
- Headless-sandbox quirk: Google Fonts / Material Symbols don't render in
  the sandbox (shows text like "local_cafe") — real browsers render fine.

## Animations (premium, zero-cost)
- All CSS + tiny vanilla JS. No libraries. Only `transform`/`opacity` are
  animated (GPU-composited); nothing layout-triggering.
- Scroll reveals (staggered), hero Ken Burns zoom, hover micro-interactions
  (fine pointers only), About stat count-ups, header shadow on scroll,
  `content-visibility:auto` on below-fold sections.
- `prefers-reduced-motion` disables everything; no-JS still shows all content
  (reveal classes are added by JS only).
- Admin → Design & Fonts → "Enable animations" toggle writes
  `theme.animations` in content.json. When off, `<html>` gets `no-anim`
  (all motion killed) and the JS skips observers/counters entirely.

## Page sections (in order)
hero → trust row → **Our Story** (`#about`) → deal of the day → menu →
combo deals → **How Ordering Works** (`#howOrder`) → gallery → reviews →
**Delivery Areas** (`#delivery`) → **FAQ** (`#faq`) → location →
**Book a Table** (`#reservation`) → QR menu → **WhatsApp circle**
(`#waSignup`) → footer.
- Our Story: 2-col desktop (image left, text right), stacked mobile; 3 stat
  counters; photo editable in Admin → Our Story (recommended 800x600).
- How Ordering Works: 3 steps (1-2-3) teaching the cart → WhatsApp flow.
- Delivery Areas: chip pills from a one-per-line list (Admin → Delivery Areas).
- FAQ: accordions collapsed by default, add/edit/delete in Admin → FAQ.
- Book a Table: name/phone/date/time/guests form → opens WhatsApp with a
  prefilled reservation message (date can't be in the past). Labels + message
  editable in Admin → Book a Table; toggleable in Admin → Page Sections.
- WhatsApp circle: ink band + "Join WhatsApp Circle" button that opens
  wa.me with a prefilled message — builds the cafe's customer list.
  Message template editable in Admin → WhatsApp Deals (`{name}` = site name).

## Live open/closed status
- `content.json` → `hours: {open, close, note}` (24h, e.g. open 08:00 /
  close 23:00; close < open means overnight).
- The site computes status from the visitor's clock: green pulsing dot +
  "Open Now • Closes 11:00 PM" or red dot + "Closed • Opens 8:00 AM".
  Shown in the hero badge, the location card, the map pin bubble, and the
  footer. Bad time strings fall back silently to the static text.
- Admin → Opening Hours (time pickers + note).

## Deal countdown
- Deal of the Day shows a live "Ends in 04:23:11" pill, ticking every
  second, counting to `dealOfDay.endsAt` (24h, default 10:00 today for the
  morning deal). After expiry it shows `dealOfDay.endedText`.
- Admin → Deal of the Day → "Deal ends at (24h)".

## Promo coupons
- `content.json` → `coupons: [{code, type: "percent"|"fixed", value, label}]`
  (sample: QUIET10 = 10% off).
- Cart drawer has a promo-code field (case-insensitive). Applied coupon shows
  as a green pill with a remove ×; totals show Subtotal / Discount (code) /
  Total. Percent is clamped to 1–100, discount never exceeds the subtotal.
- The discount line flows into the WhatsApp order message AND the orders API
  payload (`subtotal`, `discount`, `coupon`); the admin Orders tab shows the
  discount breakdown per order.
- Admin → Promo Codes (add/remove; code auto-uppercased).

## Color Palettes
- `palettes.json` is at the template root (same folder as `content.json`). It holds the 4 researched, WCAG-verified palettes: `ink` ("Ink Black"), `terracotta`, `sage`, `navy`. Each palette gives `primary`, `accent`, `onPrimary`, `onAccent`, `accentText` — see `PALETTES.md` for the verified pair table.
- Active palette is set by `content.json` → `theme.palette` (default `ink`). The site loads `palettes.json` alongside `content.json`; if the palette key is invalid or the file is missing, it falls back safely to the custom theme colors.
- Custom colors: if `theme.palette` is not set (null), the site uses `theme.primary` / `theme.accent` and auto-computes WCAG-safe on-accent / on-primary / accent-text colors automatically (including darkening the accent for small text on the cream canvas).
- Runtime CSS tokens set by the palette: `--red`, `--orange`, `--red-dark` (darkened primary), `--on-primary`, `--on-accent`, `--accent-text`.
- Admin → Design & Fonts → Color Palette (first block in the card): click a palette card, then "Save All Changes" — the palette name saves into `theme.palette` in `content.json` and the live preview reloads themed. Editing the Primary/Accent color pickers switches back to custom colors and shows a "Custom colors active" note.
- The dark deal band and WhatsApp band are intentionally NOT palette-driven (hardcoded dark ink, verified ≈14:1).

## Print menu
- "Print Menu" button above the menu opens a clean black-on-white printable
  menu (cafe name, address, categories, items + prices, "Prices include
  GST", hours) via `@media print` — the site chrome is hidden on paper.
  Button text editable in Admin → Menu.

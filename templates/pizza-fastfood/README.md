# Template 1 — Pizza / Fast Food ("Crust House" design)

Production restaurant website template. JSON-driven: all customer content lives in
`content.json`; the design never needs code edits.

## Structure
- `index.html` — public site (fetches `content.json`, renders via vanilla JS)
- `content.json` — ALL editable content: site info, hero, about/story, deal of
  day, menu (categories + items), combo deals, how-ordering-works, gallery,
  reviews, delivery areas, FAQ, location, QR, WhatsApp deals band, footer
- `images/` — local images (JPG, optimized). No remote image dependencies.
- `admin/` — password-protected admin panel (`site.com/admin`)

## New customer setup (5 minutes)
1. Copy this folder to the customer's site directory.
2. Edit `content.json` (or use the admin panel) — name, phone, WhatsApp number,
   address, timings, menu items + PKR prices, photos.
3. **Change the admin password**: open `admin/config.php`, generate a new hash:
   `php -r "echo password_hash('NEW-PASSWORD', PASSWORD_BCRYPT), PHP_EOL;"`
   and replace the hash. Give the password to the customer at handover.
   Default password is `crust123` (change before going live).
   NOTE (Oct 2026): `site.phone` / `whatsappDigits` are currently set to the
   owner's DEMO number 03193722519 for testing — replace with the customer's
   real numbers at every customer setup.
4. WhatsApp ordering uses the `site.whatsapp` number in `content.json`
   (digits only, e.g. `923001234567`) with a prefilled order message.

## Admin panel
- Login at `/admin/` — sections: Orders, Site Info, Hero, Deal of the Day, Menu
  (add/edit/delete items per category), Combo Deals, Gallery, Reviews,
  Location, QR, Footer, Cart Text, **Our Story**, **How Ordering**,
  **Delivery Areas**, **FAQ**, **WhatsApp Deals**, **Social Links**,
  **Announcement Bar**, **Design & Fonts**.
- **Page Sections** (first card): Elementor-style show/hide toggles for all
  16 page sections. Header, cart and checkout always stay visible.
- **Social Links**: add/remove rows; platform dropdown
  (Facebook/Instagram/TikTok/YouTube/X) + profile URL. Empty URL = hidden.
- **Announcement Bar**: show/hide toggle, text, optional link + link text.
  Visitors can dismiss it (×); it returns on their next visit.
- **Design & Fonts**: heading/body font dropdowns (curated Google Fonts),
  primary + accent color pickers. Save, then refresh the site.
- Every image upload field shows its recommended size (e.g. Hero 1200x800).
- Uploads: JPG/PNG/WebP, max 2MB, stored in `images/` with safe filenames.
- Saves are atomic (temp file + rename) and session-protected.

## Cart + WhatsApp checkout (how the restaurant receives orders)
1. Customer taps **ADD +** on menu items (or **Order Deal** on a combo) — items
   collect in a slide-in cart drawer (bag icon in the header, ORDER NOW button,
   or the sticky-bar WhatsApp button opens it too). Cart persists in
   localStorage.
2. In the drawer: qty steppers, subtotal, checkout form (name / phone /
   address required, notes optional; Pakistani mobile validated).
3. **Order via WhatsApp** → the order is POSTed to `api/order.php` (saved to
   `api/orders.json`) AND the customer's WhatsApp opens with the full order
   message addressed to the restaurant's `site.whatsappDigits` number.
   WhatsApp always opens even if the server save fails — the chat message is
   the primary order channel, the JSON copy is the backup/inbox.
4. The restaurant sees every order twice:
   - **WhatsApp chat**: `*New Order — {name}*` with item lines, total,
     customer name/phone/address/notes.
   - **Admin → Orders tab**: newest-first list with customer details, item
     lines, totals, New/Completed status pill, "Mark completed" and Delete.
     The tab shows a count badge of new orders.
5. All cart/checkout UI text lives in the `cart` section of `content.json`
   (editable in Admin → Cart Text) with built-in English fallbacks.

## Color palettes
- `palettes.json` (template root) defines 4 WCAG-verified preset palettes —
  `classic` (Classic Red), `midnight`, `emerald`, `royal` — each with
  `primary`, `accent`, `onPrimary`, `onAccent`, `accentText` tokens.
  See `PALETTES.md` for the contrast tables behind every pair.
- `content.json` → `theme.palette` selects a preset (default `"classic"`).
  The site loads `palettes.json` in parallel with `content.json` (a failed
  load degrades to `{}` and never breaks rendering), then sets the CSS vars
  `--red` / `--orange` / `--red-dark` / `--on-primary` / `--on-accent` /
  `--accent-text` / `--band-dk48` / `--band-dk64` from it. The Deal of the
  Day and WhatsApp bands gradient between the two darkened primary shades.
- **Custom override:** if `theme.palette` is `null`, missing or not a key in
  `palettes.json`, the site uses `theme.primary` / `theme.accent` directly
  and *computes* the on-colors in JS with a WCAG contrast helper —
  `onPrimary`/`onAccent` = the better of white vs dark ink, and
  `accentText` = the accent walked toward white in 2.5% steps until it
  passes 4.5:1 on the dark canvas (`#141414`) and surface (`#1a1a1a`).
- Admin → Design & Fonts → **Color Palette**: palette cards (name + primary /
  accent / on-primary swatches, active one highlighted). Clicking a card sets
  `theme.palette` and saves (preview reloads with the new palette). Touching
  the Primary/Accent color pickers switches back to custom mode
  (`theme.palette: null`) and shows a "Custom colors active" note.

## Notes
- Reviews ship with a "sample data" disclaimer — replace with real reviews.
- QR section is a decorative placeholder; swap in a real QR (API/service)
  per customer at deploy time.
- Fonts: Google Fonts (Poppins 600/700/800 headings + Inter body).

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
**Book a Table** (`#reservation`) → QR menu → **WhatsApp deals band**
(`#waSignup`) → footer.
- Our Story: 2-col desktop (image left, text right), stacked mobile; 3 stat
  counters; photo editable in Admin → Our Story (recommended 800x600).
- How Ordering Works: 3 steps (1-2-3) teaching the cart → WhatsApp flow.
- Delivery Areas: chip pills from a one-per-line list (Admin → Delivery Areas).
- FAQ: accordions collapsed by default, add/edit/delete in Admin → FAQ.
- Book a Table: name/phone/date/time/guests form → opens WhatsApp with a
  prefilled reservation message (date can't be in the past). Labels + message
  editable in Admin → Book a Table; toggleable in Admin → Page Sections.
- WhatsApp deals band: red gradient + "Join WhatsApp List" button that opens
  wa.me with a prefilled message — builds the restaurant's customer list.

## Live open/closed status
- `content.json` → `hours: {open, close, note}` (24h, e.g. open 12:00 /
  close 01:00; close < open means overnight).
- The site computes status from the visitor's clock: green pulsing dot +
  "Open Now • Closes 1:00 AM" or red dot + "Closed • Opens 12:00 PM".
  Shown in the hero badge, the location card, the map pin bubble, and the
  footer. Bad time strings fall back silently to the static text.
- Admin → Opening Hours (time pickers + note).

## Deal countdown
- Deal of the Day shows a live "Ends in 04:23:11" pill, ticking every
  second, counting to `dealOfDay.endsAt` (24h, default 23:59 today).
  After expiry it shows `dealOfDay.endedText`.
- Admin → Deal of the Day → "Deal ends at (24h)".

## Promo coupons
- `content.json` → `coupons: [{code, type: "percent"|"fixed", value, label}]`
  (sample: CRUST10 = 10% off).
- Cart drawer has a promo-code field (case-insensitive). Applied coupon shows
  as a green pill with a remove ×; totals show Subtotal / Discount (code) /
  Total. Percent is clamped to 1–100, discount never exceeds the subtotal.
- The discount line flows into the WhatsApp order message AND the orders API
  payload (`subtotal`, `discount`, `coupon`); the admin Orders tab shows the
  discount breakdown per order.
- Admin → Promo Codes (add/remove; code auto-uppercased).

## Print menu
- "Print Menu" button above the menu opens a clean black-on-white printable
  menu (restaurant name, address, categories, items + prices, "Prices include
  GST", hours) via `@media print` — the site chrome is hidden on paper.
  Button text editable in Admin → Menu.
  Message template editable in Admin → WhatsApp Deals (`{name}` = site name).

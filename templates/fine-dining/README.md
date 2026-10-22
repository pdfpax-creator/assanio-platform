# Template 4 — Fine Dining ("NOORÉ" design)

Production fine-dining restaurant website template. JSON-driven: all customer
content lives in `content.json`; the design never needs code edits.

Design language: deep black canvas (#131313), warm gold primary (#ecc246)
with antique-gold accents (#c9a227), warm ivory text (#e5e2e1), Playfair
Display headings with generous letter-spacing, completely sharp geometry
(0px corner radius per the NOORÉ design system), gold hairline dividers,
cinematic dark hero. The reservation section is the primary conversion
surface — elevated background + gold-trimmed form.

## Structure
- `index.html` — public site (fetches `content.json`, renders via vanilla JS)
- `content.json` — ALL editable content: site info, hero, about/philosophy,
  deal of the day, menu (categories + items), combo deals, how-ordering-works,
  gallery, reviews, delivery areas, FAQ, location, QR, WhatsApp deals band,
  footer, announcement, socials, theme, visibility toggles, hours, coupons,
  reservation text
- `images/` — 4 local AI-generated photos (454KB total, JPG, ≤1200px wide,
  q72) + `favicon.svg` (gold "N" monogram, inline SVG — never breaks).
  No remote image dependencies.
- `admin/` — password-protected admin panel (`site.com/admin`)
- `api/order.php` + `api/orders.json` — order inbox backend (ships as `[]`)

## New customer setup (5 minutes)
1. Copy this folder to the customer's site directory.
2. Edit `content.json` (or use the admin panel) — name, phone, WhatsApp number,
   address, timings, menu items + PKR prices, photos.
3. **Change the admin password**: open `admin/config.php`, generate a new hash:
   `php -r "echo password_hash('NEW-PASSWORD', PASSWORD_BCRYPT), PHP_EOL;"`
   and replace the hash. Give the password to the customer at handover.
   Default password is `noore123` (change before going live).
   NOTE (Oct 2026): `site.phone` / `whatsappDigits` are currently set to the
   owner's DEMO number 03193722519 for testing — replace with the customer's
   real numbers at every customer setup.
4. WhatsApp ordering uses the `site.whatsapp` number in `content.json`
   (digits only, e.g. `923001234567`) with a prefilled order message.

## Photos
- `hero-dish.jpg` — smoked lamb chops, fine-dining plating, dark background
  (hero background, signature menu items, Chef's Tasting deal)
- `interior.jpg` — candlelit salon interior (Our Philosophy, Private Dining deal)
- `dessert.jpg` — gold-leaf kulfi plating (Gold-Leaf Kulfi menu item)
- `table.jpg` — candlelit table for two (Dinner for Two deal)
- All AI-generated for this template. Recommended upload sizes shown in the
  admin (hero 1200x800, item/gallery 800x600). Keep replacements ≤600KB total.

## Color palettes
- `palettes.json` holds 4 curated, WCAG-verified palettes: **Gold** (default),
  **Platinum**, **Rosewood**, **Emerald & Gold**. Each palette is a full token
  set — `primary`, `accent`, `onPrimary`, `onAccent`, `accentText` — and the
  site is fully token-driven: text on a primary/accent surface always uses the
  palette's `onPrimary`/`onAccent`, never an assumed ink. Emerald & Gold is
  the proof: its primary is deep emerald so `onPrimary` is **white**, while
  the gold-family palettes use dark ink.
- `content.json` → `theme.palette` selects the palette (Admin → Design &
  Fonts → Color Palette renders the 4 swatch cards). Editing the primary or
  accent color picker switches to **Custom**: `theme.palette` becomes `null`
  and the site computes safe inks itself (best of white/dark by WCAG contrast;
  accent text is lightened until it passes 4.5:1 on the dark canvas/surface).
- Derived tokens: `--red-dark` = darken(primary, 38) and `--band-dk32` =
  darken(primary, 32), the announcement-bar gradient start.
- If `palettes.json` is missing or fails to load, the site falls back to the
  custom-color path — rendering never breaks.
- **Fixed brand dressing (not palette pairs, intentionally unchanged):** the
  gold hairline borders `rgba(201,162,39,*)`, the dark deal-of-the-day band
  and reservation gradients, and the hardcoded-gold WhatsApp deals button
  stay gold in every palette.

## Admin panel
- Login at `/admin/` — sections: Orders, Site Info, Opening Hours, Hero,
  Deal of the Day, Promo Codes, Menu (add/edit/delete items per category),
  Combo Deals, Gallery, Reviews, Location, Book a Table, QR Menu, Footer,
  Cart Text, **Our Story**, **How Ordering**, **Delivery Areas**, **FAQ**,
  **WhatsApp Deals**, **Social Links**, **Announcement Bar**, **Design &
  Fonts**.
- **Page Sections** (first card): Elementor-style show/hide toggles for all
  17 page sections. Header, cart and checkout always stay visible.
- **Social Links**: add/remove rows; platform dropdown
  (Facebook/Instagram/TikTok/YouTube/X) + profile URL. Empty URL = hidden.
- **Announcement Bar**: show/hide toggle, text, optional link + link text.
  Visitors can dismiss it (×); it returns on their next visit.
- **Design & Fonts**: heading/body font dropdowns (curated Google Fonts —
  default Playfair Display + Inter), primary + accent color pickers
  (defaults #ecc246 / #c9a227). Save, then refresh the site.
- Every image upload field shows its recommended size (e.g. Hero 1200x800).
- Uploads: JPG/PNG/WebP, max 2MB, stored in `images/` with safe filenames.
- Saves are atomic (temp file + rename) and session-protected.

## Cart + WhatsApp checkout (how the restaurant receives orders)
1. Customer taps **ADD +** on menu items (or **Order Deal** on a tasting
   menu) — items collect in a slide-in cart drawer (bag icon in the header,
   RESERVE A TABLE button, or the sticky-bar WhatsApp button opens it too).
   Cart persists in localStorage.
2. In the drawer: qty steppers, promo-code field, subtotal, checkout form
   (name / phone / address required, notes optional; Pakistani mobile
   validated). Errors are a persistent red box that hides as the user types.
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

## Notes
- Reviews ship with a "sample data" disclaimer — replace with real reviews.
- QR section is a decorative placeholder; swap in a real QR (API/service)
  per customer at deploy time.
- Fonts: Google Fonts (Playfair Display headings + Inter body, light weights).

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
hero → trust row → **Our Philosophy** (`#about`) → deal of the day → menu →
combo deals → **How Your Evening Works** (`#howOrder`) → gallery → reviews →
**Delivery Areas** (`#delivery`) → **FAQ** (`#faq`) → location →
**Book a Table** (`#reservation`, primary conversion) → QR menu →
**WhatsApp private list** (`#waSignup`) → footer.
- Our Philosophy: 2-col desktop (image left, text right), stacked mobile; 3
  stat counters; photo editable in Admin → Our Story (recommended 800x600).
- How Your Evening Works: 3 steps (browse courses → reserve/order → we take
  it from here).
- Delivery Areas: sharp-corner chips from a one-per-line list (Admin →
  Delivery Areas).
- FAQ: accordions collapsed by default, add/edit/delete in Admin → FAQ.
- Book a Table: name/phone/date/time/guests form → opens WhatsApp with a
  prefilled reservation message (date can't be in the past). Visually
  elevated (gradient band + gold-trimmed form) as the primary conversion.
  Labels + message editable in Admin → Book a Table; toggleable in Admin →
  Page Sections.
- WhatsApp deals band: dark + gold hairlines + "Join the Private List" button
  that opens wa.me with a prefilled message — builds the restaurant's
  private guest list.

## Live open/closed status
- `content.json` → `hours: {open, close, note}` (24h, e.g. open 12:00 /
  close 23:30; close < open means overnight). Default: Tue–Sun.
- The site computes status from the visitor's clock: green pulsing dot +
  "Open Now • Closes 11:30 PM" or red dot + "Closed • Opens 12:00 PM".
  Shown in the hero badge, the location card, the map pin bubble, and the
  footer. Bad time strings fall back silently to the static text.
- Admin → Opening Hours (time pickers + note).

## Deal countdown
- Deal of the Day shows a live "Ends in 04:23:11" pill, ticking every
  second, counting to `dealOfDay.endsAt` (24h, default 23:30 today).
  After expiry it shows `dealOfDay.endedText`.
- Admin → Deal of the Day → "Deal ends at (24h)".

## Promo coupons
- `content.json` → `coupons: [{code, type: "percent"|"fixed", value, label}]`
  (sample: NOORE15 = 15% off).
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

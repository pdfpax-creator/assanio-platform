# Template 5 — Real Estate ("Emerald Estates" design)

Production real-estate website template for Assanio. JSON-driven: all content lives in
`content.json`; the design never needs code edits. Built mobile-first — 90% of property
browsers in Pakistan are on phones.

Design: warm paper canvas (`#fafaf7`), deep navy primary (`#1b2a4a`), gold accent
(`#c9a227`), Fraunces serif headings + Inter body — a premium, trustworthy property-portal
aesthetic. Flat solid colours, rectangular badges and buttons, inline SVG icons only
(no icon font — it fails on some mobile browsers).

## Sections
- Announcement bar, sticky header (logo, nav, phone, WhatsApp CTA)
- Hero with **property search** (location, type, buy/rent, max price, bedrooms)
- Featured properties grid (photo, For Sale/For Rent badge, price, beds/baths/area, WhatsApp inquiry)
- **Property detail modal** (gallery, features, description, agent card, Schedule Visit, WhatsApp)
- Why Choose Us (4 trust badges), About + animated stats
- Agents (photo, role, call + WhatsApp), Testimonials carousel
- **Site Visit booking form** → opens WhatsApp with the request pre-written
- Contact (address, phone, email, hours, map placeholder), footer
- Sticky mobile bottom bar (Call Now + WhatsApp), floating WhatsApp button

## Structure
- `index.html` — public site (fetches `content.json` + `palettes.json`, vanilla JS)
- `content.json` — ALL editable content: site info, hero, search options, properties
  (8 samples), agents (3), why-us, about, testimonials, visit form, contact, footer,
  WhatsApp message templates, every button label (`ui` block)
- `palettes.json` — 8 WCAG-conscious palettes (navy, emerald, charcoal, burgundy, teal, slate, forest, royal)
- `images/` — property/agent photos. Demo uses `https://picsum.photos/seed/…` placeholders;
  replace with real photos at customer setup (or upload via the admin panel)
- `admin/` — password-protected admin panel (`/admin/`). **Every field has hint text.**
  Default password is `emerald123` — change before going live (see below).

## Admin panel (built for non-technical users)
Sections: Page Sections (show/hide), **Properties** (add/edit/delete with full form),
Hero & Search (headline + filter options), Site Info, Agents (add/edit/delete),
Why Choose Us, About (+ stats), Testimonials (add/edit/delete), Visit Booking,
Contact, Footer, WhatsApp Messages (editable templates), Button & Label Texts,
Social Links, Announcement Bar, Design & Fonts (palette picker + fonts + animations).

Property form fields: title, type, purpose (sale/rent), price (digits), price label,
location, area (must match a search location), beds, baths, plot size, covered area,
main photo (+ upload), gallery (one link per line), features (one per line),
description, assigned agent, featured flag.

## New customer setup (10 minutes)
1. Copy this folder to the customer's site directory.
2. Log in to `/admin/` (password `emerald123`) and fill in: Site Info, properties,
   agents, about, contact. Or edit `content.json` directly.
3. **Change the admin password**: open `admin/config.php`, generate a new hash:
   `php -r "echo password_hash('NEW-PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"`
   and replace `CH_ADMIN_HASH`. Give the password to the customer at handover.
4. Replace all `picsum.photos` images with the customer's real property photos
   (upload via the admin panel image fields).
5. **Set the real WhatsApp number**: Site Info → WhatsApp (digits), e.g. `923001234567`.
   Every WhatsApp button, inquiry and visit request uses it.
6. Check the search Locations list matches the Area values on the properties.

## Search behaviour
- Location dropdown matches the property's `area` (or location text)
- Max price compares the numeric `price` (digits only — keep it clean)
- Bedrooms filter needs `beds > 0`; plots/commercial use 0 and are excluded from bed filtering
- "Rs 5 Crore+" shows sale properties priced at/above Rs 5 Crore
- Featured properties always sort first

## Notes
- Pinch zoom is disabled and inputs are 16px on mobile (no iOS auto-zoom)
- `visibility` in content.json toggles sections on/off from the admin
- WhatsApp messages support placeholders: {agency} {title} {price} {location} {name} {phone} {property} {date} {time}

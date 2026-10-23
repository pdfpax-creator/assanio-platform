# Real Estate — Colour Palettes

Eight palettes in `palettes.json`, each with `primary`, `accent`, `onPrimary`,
`onAccent` and `accentText`. The site picks `theme.palette` from `content.json`
(default `"navy"`); the admin panel offers a visual picker.

## Palette list

| Key        | Name               | Primary   | Accent    | Feel                          |
|------------|--------------------|-----------|-----------|-------------------------------|
| `navy`     | Midnight Navy      | `#1b2a4a` | `#c9a227` | Classic trust + premium gold  |
| `emerald`  | Emerald Trust      | `#0e5e4a` | `#c9a227` | Fresh, financial confidence    |
| `charcoal` | Charcoal Elite     | `#232323` | `#b08d3e` | Ultra-premium, minimal         |
| `burgundy` | Burgundy Heritage  | `#6b1f2a` | `#c9a227` | Established, old-money Lahore  |
| `teal`     | Teal Modern        | `#0f5e68` | `#e8a33d` | Contemporary, young agency     |
| `slate`    | Slate Professional | `#334155` | `#d97706` | Corporate, no-nonsense         |
| `forest`   | Forest Luxury      | `#1e3d2f` | `#c6a15b` | Farmhouse / DHA-phase-9 vibe   |
| `royal`    | Royal Blue         | `#1e3a8a` | `#f59e0b` | Bold, high-energy marketing    |

## Contrast notes (WCAG)

- All dark primaries pair with white `onPrimary` (contrast 8:1 or better).
- Gold/bronze accents use a dark `onAccent` (`#1b1b1b` or the primary) — white text on
  gold fails WCAG, dark text on gold passes.
- `accentText` is a darkened gold (`#8a6d1a` family) so small uppercase kickers stay
  readable on the paper canvas (`#fafaf7`) — 4.5:1 or better.
- `slate` is the exception: its orange accent (`#d97706`) is dark enough for white
  `onAccent` text.

## Changing palettes

Admin → Design & Fonts → click a palette card → Save All Changes.
Or edit `content.json`: `"theme": { "palette": "emerald" }`.

Do not hand-pick new colours without checking contrast: body text is `#1f2937` on
`#fafaf7` (14:1), muted text `#5b6472` on `#fafaf7` (~7:1).

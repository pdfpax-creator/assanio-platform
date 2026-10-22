# Palette Research — Pizza / Fast-Food — "Crust House" (dark theme)

Researched & computed 2026-10-20 with a WCAG 2.1 contrast calculator (relative
luminance per spec; ratio = (L1+0.05)/(L2+0.05)). Thresholds: **4.5:1** normal
text, **3:1** large text (≥18pt, or ≥14pt bold) and non-text graphics.
No palette ships unless EVERY pair below passes — this is the guardrail
against white-on-white / red-on-red failures: the on-color is *computed*
(#ffffff vs #111111, whichever passes), never assumed.

Deal band gradient (implementer formula): `linear-gradient(135deg, darken(primary,48) 0%, primary 55%, darken(primary,64) 100%)` — replaces the hardcoded #c1121f/#a30f1d ends. Band ink #ffffff. Announcement gradient: red-dark → primary, ink = onPrimary. `--red-dark` = darken(primary,38) (existing JS).

Tokens per palette (in `palettes.json`): `primary`, `accent`, `onPrimary`,
`onAccent`, `accentText` (accent darkened/lightened until small accent text —
kickers, offer lines — passes 4.5:1 on canvas AND surface). `--red` = primary,
`--orange` = accent, `--red-dark` = darken(primary,38) auto.


## classic

`primary #d62839` · `accent #f4a261` · `onPrimary #ffffff` · `onAccent #141414` · `accentText #f4a261` · `red-dark (auto) #b00213`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #d62839 | 4.97:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #b00213 | 7.33:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #f4a261 | 8.94:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #f4a261 | #141414 | 8.94:1 | 4.5:1 | PASS |
| A5 accentText/surface | #f4a261 | #1a1a1a | 8.44:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #f4a261 | #20201f | 7.91:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #f4a261 | #141414 | 8.94:1 | 3.0:1 | PASS |
| D1 #fff/band mid = primary | #ffffff | #d62839 | 4.97:1 | 4.5:1 | PASS |
| D2 #fff/band start = darken48 | #ffffff | #a60009 | 8.0:1 | 4.5:1 | PASS |
| D3 #fff/band end = darken64 | #ffffff | #960000 | 9.14:1 | 4.5:1 | PASS |

## midnight

`primary #2563eb` · `accent #38bdf8` · `onPrimary #ffffff` · `onAccent #141414` · `accentText #38bdf8` · `red-dark (auto) #003dc5`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #2563eb | 5.17:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #003dc5 | 8.49:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #38bdf8 | 8.6:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #38bdf8 | #141414 | 8.6:1 | 4.5:1 | PASS |
| A5 accentText/surface | #38bdf8 | #1a1a1a | 8.12:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #38bdf8 | #20201f | 7.61:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #38bdf8 | #141414 | 8.6:1 | 3.0:1 | PASS |
| D1 #fff/band mid = primary | #ffffff | #2563eb | 5.17:1 | 4.5:1 | PASS |
| D2 #fff/band start = darken48 | #ffffff | #0033bb | 9.58:1 | 4.5:1 | PASS |
| D3 #fff/band end = darken64 | #ffffff | #0023ab | 11.49:1 | 4.5:1 | PASS |

## emerald

`primary #047857` · `accent #fbbf24` · `onPrimary #ffffff` · `onAccent #141414` · `accentText #fbbf24` · `red-dark (auto) #005231`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #047857 | 5.48:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #005231 | 9.33:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #fbbf24 | 11.04:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #fbbf24 | #141414 | 11.04:1 | 4.5:1 | PASS |
| A5 accentText/surface | #fbbf24 | #1a1a1a | 10.43:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #fbbf24 | #20201f | 9.77:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #fbbf24 | #141414 | 11.04:1 | 3.0:1 | PASS |
| D1 #fff/band mid = primary | #ffffff | #047857 | 5.48:1 | 4.5:1 | PASS |
| D2 #fff/band start = darken48 | #ffffff | #004827 | 10.73:1 | 4.5:1 | PASS |
| D3 #fff/band end = darken64 | #ffffff | #003817 | 13.31:1 | 4.5:1 | PASS |

## royal

`primary #7c3aed` · `accent #e879f9` · `onPrimary #ffffff` · `onAccent #141414` · `accentText #e879f9` · `red-dark (auto) #5614c7`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #7c3aed | 5.7:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #5614c7 | 9.05:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #e879f9 | 7.49:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #e879f9 | #141414 | 7.49:1 | 4.5:1 | PASS |
| A5 accentText/surface | #e879f9 | #1a1a1a | 7.07:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #e879f9 | #20201f | 6.63:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #e879f9 | #141414 | 7.49:1 | 3.0:1 | PASS |
| D1 #fff/band mid = primary | #ffffff | #7c3aed | 5.7:1 | 4.5:1 | PASS |
| D2 #fff/band start = darken48 | #ffffff | #4c0abd | 10.07:1 | 4.5:1 | PASS |
| D3 #fff/band end = darken64 | #ffffff | #3c00ad | 11.7:1 | 4.5:1 | PASS |

# Palette Research — Fine Dining — "NOORÉ" (dark luxury theme)

Researched & computed 2026-10-20 with a WCAG 2.1 contrast calculator (relative
luminance per spec; ratio = (L1+0.05)/(L2+0.05)). Thresholds: **4.5:1** normal
text, **3:1** large text (≥18pt, or ≥14pt bold) and non-text graphics.
No palette ships unless EVERY pair below passes — this is the guardrail
against white-on-white / red-on-red failures: the on-color is *computed*
(#ffffff vs #111111, whichever passes), never assumed.

Announcement gradient: `linear-gradient(90deg, darken(primary,32), primary)`, ink = onPrimary (dark on gold family). Deal band is palette-independent dark (#0d0c0b→#1c1b1a) with accent borders. `--red-dark` = darken(primary,38). NOTE: several shipped rules hardcode #fff text on var(--red) (e.g. .cartcount) — with gold-family primaries that is ≈1.9:1 and MUST move to var(--on-primary).

Tokens per palette (in `palettes.json`): `primary`, `accent`, `onPrimary`,
`onAccent`, `accentText` (accent darkened/lightened until small accent text —
kickers, offer lines — passes 4.5:1 on canvas AND surface). `--red` = primary,
`--orange` = accent, `--red-dark` = darken(primary,38) auto.


## gold

`primary #ecc246` · `accent #c9a227` · `onPrimary #111111` · `onAccent #141414` · `accentText #c9a227` · `red-dark (auto) #c69c20`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #111111 | #ecc246 | 11.12:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #111111 | #c69c20 | 7.35:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #c9a227 | 7.62:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #c9a227 | #131313 | 7.68:1 | 4.5:1 | PASS |
| A5 accentText/surface | #c9a227 | #1c1b1b | 7.1:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #c9a227 | #201f1f | 6.8:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #c9a227 | #131313 | 7.68:1 | 3.0:1 | PASS |
| F1 onPrimary/darken32 (annbar gradient start) | #111111 | #cca226 | 7.88:1 | 4.5:1 | PASS |

## platinum

`primary #d9d9de` · `accent #a7a7b3` · `onPrimary #111111` · `onAccent #141414` · `accentText #a7a7b3` · `red-dark (auto) #b3b3b8`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #111111 | #d9d9de | 13.43:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #111111 | #b3b3b8 | 9.04:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #a7a7b3 | 7.74:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #a7a7b3 | #131313 | 7.8:1 | 4.5:1 | PASS |
| A5 accentText/surface | #a7a7b3 | #1c1b1b | 7.22:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #a7a7b3 | #201f1f | 6.91:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #a7a7b3 | #131313 | 7.8:1 | 3.0:1 | PASS |
| F1 onPrimary/darken32 (annbar gradient start) | #111111 | #b9b9be | 9.66:1 | 4.5:1 | PASS |

## rosewood

`primary #e0a899` · `accent #b76e79` · `onPrimary #111111` · `onAccent #141414` · `accentText #b76e79` · `red-dark (auto) #ba8273`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #111111 | #e0a899 | 9.2:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #111111 | #ba8273 | 5.87:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #b76e79 | 4.84:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #b76e79 | #131313 | 4.88:1 | 4.5:1 | PASS |
| A5 accentText/surface | #b76e79 | #1c1b1b | 4.52:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #b76e79 | #201f1f | 4.32:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #b76e79 | #131313 | 4.88:1 | 3.0:1 | PASS |
| F1 onPrimary/darken32 (annbar gradient start) | #111111 | #c08879 | 6.33:1 | 4.5:1 | PASS |

## emeraldgold

`primary #065f46` · `accent #d4af37` · `onPrimary #ffffff` · `onAccent #141414` · `accentText #d4af37` · `red-dark (auto) #003920`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #065f46 | 7.68:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #003920 | 13.08:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #141414 | #d4af37 | 8.76:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #d4af37 | #131313 | 8.84:1 | 4.5:1 | PASS |
| A5 accentText/surface | #d4af37 | #1c1b1b | 8.17:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #d4af37 | #201f1f | 7.82:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #d4af37 | #131313 | 8.84:1 | 3.0:1 | PASS |
| F1 onPrimary/darken32 (annbar gradient start) | #ffffff | #003f26 | 12.08:1 | 4.5:1 | PASS |

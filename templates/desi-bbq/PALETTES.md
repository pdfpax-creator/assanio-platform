# Palette Research — Desi BBQ — "Dera BBQ House" (light warm theme)

Researched & computed 2026-10-20 with a WCAG 2.1 contrast calculator (relative
luminance per spec; ratio = (L1+0.05)/(L2+0.05)). Thresholds: **4.5:1** normal
text, **3:1** large text (≥18pt, or ≥14pt bold) and non-text graphics.
No palette ships unless EVERY pair below passes — this is the guardrail
against white-on-white / red-on-red failures: the on-color is *computed*
(#ffffff vs #111111, whichever passes), never assumed.

Deal band gradient (implementer formula): `linear-gradient(135deg, darken(primary,28) 0%, primary 70%)` — replaces the accent→primary gradient whose START failed 4.5:1 with the near-white band ink. Band ink #fff8f0. Countdown pill rgba(0,0,0,.35) over the band only darkens it further, so pill text inherits the band pass. `--red-dark` = darken(primary,38).

Tokens per palette (in `palettes.json`): `primary`, `accent`, `onPrimary`,
`onAccent`, `accentText` (accent darkened/lightened until small accent text —
kickers, offer lines — passes 4.5:1 on canvas AND surface). `--red` = primary,
`--orange` = accent, `--red-dark` = darken(primary,38) auto.


## classic

`primary #9b4003` · `accent #bb581e` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #b6561d` · `red-dark (auto) #751a00`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #9b4003 | 6.71:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #751a00 | 11.03:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #bb581e | 4.64:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #b6561d | #fff8f0 | 4.6:1 | 4.5:1 | PASS |
| A5 accentText/surface | #b6561d | #ffffff | 4.84:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #bb581e | #f7ede0 | 4.01:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #bb581e | #fff8f0 | 4.4:1 | 3.0:1 | PASS |
| L1 band ink #fff8f0/primary (band end, btn label) | #fff8f0 | #9b4003 | 6.37:1 | 4.5:1 | PASS |
| L2 band ink #fff8f0/darken28 (band start) | #fff8f0 | #7f2400 | 9.25:1 | 4.5:1 | PASS |
| L3 primary/surface (stat numbers, large) | #9b4003 | #ffffff | 6.71:1 | 3.0:1 | PASS |
| L4 primary/surface2 (stat numbers, large) | #9b4003 | #f7ede0 | 5.8:1 | 3.0:1 | PASS |

## maroon

`primary #7f1d2e` · `accent #a16207` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #a16207` · `red-dark (auto) #590008`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #7f1d2e | 9.92:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #590008 | 14.7:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #a16207 | 4.92:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #a16207 | #fff8f0 | 4.67:1 | 4.5:1 | PASS |
| A5 accentText/surface | #a16207 | #ffffff | 4.92:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #a16207 | #f7ede0 | 4.25:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #a16207 | #fff8f0 | 4.67:1 | 3.0:1 | PASS |
| L1 band ink #fff8f0/primary (band end, btn label) | #fff8f0 | #7f1d2e | 9.41:1 | 4.5:1 | PASS |
| L2 band ink #fff8f0/darken28 (band start) | #fff8f0 | #630112 | 12.92:1 | 4.5:1 | PASS |
| L3 primary/surface (stat numbers, large) | #7f1d2e | #ffffff | 9.92:1 | 3.0:1 | PASS |
| L4 primary/surface2 (stat numbers, large) | #7f1d2e | #f7ede0 | 8.57:1 | 3.0:1 | PASS |

## forest

`primary #166534` · `accent #0f766e` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #0f766e` · `red-dark (auto) #003f0e`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #166534 | 7.13:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #003f0e | 12.23:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #0f766e | 5.47:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #0f766e | #fff8f0 | 5.2:1 | 4.5:1 | PASS |
| A5 accentText/surface | #0f766e | #ffffff | 5.47:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #0f766e | #f7ede0 | 4.73:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #0f766e | #fff8f0 | 5.2:1 | 3.0:1 | PASS |
| L1 band ink #fff8f0/primary (band end, btn label) | #fff8f0 | #166534 | 6.77:1 | 4.5:1 | PASS |
| L2 band ink #fff8f0/darken28 (band start) | #fff8f0 | #004918 | 10.14:1 | 4.5:1 | PASS |
| L3 primary/surface (stat numbers, large) | #166534 | #ffffff | 7.13:1 | 3.0:1 | PASS |
| L4 primary/surface2 (stat numbers, large) | #166534 | #f7ede0 | 6.16:1 | 3.0:1 | PASS |

## indigo

`primary #3730a3` · `accent #be185d` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #be185d` · `red-dark (auto) #110a7d`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #3730a3 | 9.93:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #110a7d | 15.4:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #be185d | 6.04:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #be185d | #fff8f0 | 5.73:1 | 4.5:1 | PASS |
| A5 accentText/surface | #be185d | #ffffff | 6.04:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #be185d | #f7ede0 | 5.22:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #be185d | #fff8f0 | 5.73:1 | 3.0:1 | PASS |
| L1 band ink #fff8f0/primary (band end, btn label) | #fff8f0 | #3730a3 | 9.43:1 | 4.5:1 | PASS |
| L2 band ink #fff8f0/darken28 (band start) | #fff8f0 | #1b1487 | 13.32:1 | 4.5:1 | PASS |
| L3 primary/surface (stat numbers, large) | #3730a3 | #ffffff | 9.93:1 | 3.0:1 | PASS |
| L4 primary/surface2 (stat numbers, large) | #3730a3 | #f7ede0 | 8.58:1 | 3.0:1 | PASS |

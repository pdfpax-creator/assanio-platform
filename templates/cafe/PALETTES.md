# Palette Research — Cafe — "The Quiet Cup" (light editorial theme)

Researched & computed 2026-10-20 with a WCAG 2.1 contrast calculator (relative
luminance per spec; ratio = (L1+0.05)/(L2+0.05)). Thresholds: **4.5:1** normal
text, **3:1** large text (≥18pt, or ≥14pt bold) and non-text graphics.
No palette ships unless EVERY pair below passes — this is the guardrail
against white-on-white / red-on-red failures: the on-color is *computed*
(#ffffff vs #111111, whichever passes), never assumed.

Deal band is palette-independent (hardcoded dark #141210→#232019, ink #f6f0e7, ≈14:1) — only its accent border follows the palette. Announcement bar is solid primary with white ink. Button hover swaps bg to accent, so accent must also carry white at 4.5:1 (pair C2). `--red-dark` = darken(primary,38).

Tokens per palette (in `palettes.json`): `primary`, `accent`, `onPrimary`,
`onAccent`, `accentText` (accent darkened/lightened until small accent text —
kickers, offer lines — passes 4.5:1 on canvas AND surface). `--red` = primary,
`--orange` = accent, `--red-dark` = darken(primary,38) auto.


## ink

`primary #181612` · `accent #8d4d35` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #8d4d35` · `red-dark (auto) #000000`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #181612 | 18.07:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #000000 | 21.0:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #8d4d35 | 6.47:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #8d4d35 | #fff9ef | 6.18:1 | 4.5:1 | PASS |
| A5 accentText/surface | #8d4d35 | #ffffff | 6.47:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #8d4d35 | #f9f3ea | 5.87:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #8d4d35 | #fff9ef | 6.18:1 | 3.0:1 | PASS |
| C1 #fff/primary (btn labels forced white) | #ffffff | #181612 | 18.07:1 | 4.5:1 | PASS |
| C2 #fff/accent (btn hover bg, cart badge) | #ffffff | #8d4d35 | 6.47:1 | 4.5:1 | PASS |

## terracotta

`primary #7c3f27` · `accent #9c4a1f` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #9c4a1f` · `red-dark (auto) #561901`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #7c3f27 | 8.09:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #561901 | 13.68:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #9c4a1f | 6.15:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #9c4a1f | #fff9ef | 5.87:1 | 4.5:1 | PASS |
| A5 accentText/surface | #9c4a1f | #ffffff | 6.15:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #9c4a1f | #f9f3ea | 5.58:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #9c4a1f | #fff9ef | 5.87:1 | 3.0:1 | PASS |
| C1 #fff/primary (btn labels forced white) | #ffffff | #7c3f27 | 8.09:1 | 4.5:1 | PASS |
| C2 #fff/accent (btn hover bg, cart badge) | #ffffff | #9c4a1f | 6.15:1 | 4.5:1 | PASS |

## sage

`primary #3f4f3a` · `accent #5f7161` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #5f7161` · `red-dark (auto) #192914`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #3f4f3a | 8.78:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #192914 | 15.34:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #5f7161 | 5.22:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #5f7161 | #fff9ef | 4.99:1 | 4.5:1 | PASS |
| A5 accentText/surface | #5f7161 | #ffffff | 5.22:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #5f7161 | #f9f3ea | 4.73:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #5f7161 | #fff9ef | 4.99:1 | 3.0:1 | PASS |
| C1 #fff/primary (btn labels forced white) | #ffffff | #3f4f3a | 8.78:1 | 4.5:1 | PASS |
| C2 #fff/accent (btn hover bg, cart badge) | #ffffff | #5f7161 | 5.22:1 | 4.5:1 | PASS |

## navy

`primary #1e3a5f` · `accent #96690f` · `onPrimary #ffffff` · `onAccent #ffffff` · `accentText #96690f` · `red-dark (auto) #001439`

| Pair | Text | On | Ratio | Needs | |
|---|---|---|---:|---:|---|
| A1 onPrimary/primary | #ffffff | #1e3a5f | 11.5:1 | 4.5:1 | PASS |
| A2 onPrimary/red-dark(annbar start, btn active) | #ffffff | #001439 | 18.12:1 | 4.5:1 | PASS |
| A3 onAccent/accent (tags, ribbons) | #ffffff | #96690f | 4.86:1 | 4.5:1 | PASS |
| A4 accentText/canvas (kicker, offer line) | #96690f | #fff9ef | 4.64:1 | 4.5:1 | PASS |
| A5 accentText/surface | #96690f | #ffffff | 4.86:1 | 4.5:1 | PASS |
| A6 accent/surface2 (stat numbers 22px bold, large) | #96690f | #f9f3ea | 4.4:1 | 3.0:1 | PASS |
| A7 accent/canvas (icons, graphic) | #96690f | #fff9ef | 4.64:1 | 3.0:1 | PASS |
| C1 #fff/primary (btn labels forced white) | #ffffff | #1e3a5f | 11.5:1 | 4.5:1 | PASS |
| C2 #fff/accent (btn hover bg, cart badge) | #ffffff | #96690f | 4.86:1 | 4.5:1 | PASS |

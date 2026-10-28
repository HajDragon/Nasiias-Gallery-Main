---
paths:
  - 'resources/css/**'
---

# Css

## Use main-* design tokens from nastiia's gallery handoff
Design colors live in resources/css/app.css as :root/.dark vars (main-canvas, main-surface, main-ink, main-muted, main-blue, main-on-blue, main-line, main-chip, main-red), exposed via @theme inline as Tailwind utilities (bg-main-blue, text-main-ink, ...). Values come from nastiia's gallery ontwerp-overdracht.html handoff palette. Never hardcode hex colors in components — use a main-* utility or var(--main-*); add new palette entries there only, in both light and .dark variants.

## Use ported fonts via font tokens
Design fonts live in @font-face declarations in public/css/nastia.css (Libron body, Magilio display) served from public/fonts/. Tailwind tokens in resources/css/app.css @theme: font-sans → Libron (body/text), font-display → Magilio (headings). Never introduce another font family — use font-sans / font-display utilities.

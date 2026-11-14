# TalentHub — Design System Specification

## 1. Brand Color Palette
- **Primary Green:** `#1D6A34` (`--c-primary`) - Core brand identity, primary buttons, key accents
- **Secondary Green:** `#37CE97` (`--c-mint`) - Energy, badges, secondary highlights, interactive hovers
- **Dark Green:** `#1A532A` (`--c-primary-dark`) - High-contrast text, headers, deep containers
- **Background Light:** `#F9FBFA` (`--c-bg`) - Clean canvas, elevated card surfaces
- **Deep Teal:** `#1B5F52` (`--c-teal`) - Supporting depth, gradient anchors, card accents

## 2. Typography
- **Primary Body Font:** `Inter`, system-ui, -apple-system, sans-serif
- **Headings & Accents:** `Plus Jakarta Sans`, sans-serif
- **Font Assets:** Self-hosted variable Latin subsets preloaded in `<head>` (`inter-latin-var.woff2`, `plus-jakarta-sans-latin-var.woff2`).

## 3. Elevation & Radii
- **Radii:** `sm: 4px`, `md: 8px`, `lg: 12px`, `xl: 16px`, `pill: 9999px`
- **Shadows:**
  - `shadow-sm: 0 1px 2px rgba(0,0,0,0.05)`
  - `shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)`
  - `shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)`
  - `shadow-hover: 0 20px 25px -5px rgba(29,106,52,0.15)`

## 4. Motion Architecture
- **Layer:** `public/assets/css/motion.css` & `public/assets/js/motion.js`
- **Class triggers:** `data-reveal`, `th-card-hover`, `data-stagger`
- **Performance:** Hardware-accelerated (`transform`, `opacity`), rAF throttling, `prefers-reduced-motion` override.


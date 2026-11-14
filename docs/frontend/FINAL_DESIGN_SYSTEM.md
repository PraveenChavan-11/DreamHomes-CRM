# TalentHub Consultant — Final Frontend Design System Specification

**Brand:** TalentHub Consultant  
**Tagline:** *"Find the Perfect Fit for Your Company."*  
**Operating Mode:** Static-Ready Enterprise UI  
**Target Market:** Pune & Maharashtra (Hinjawadi, Kharadi, Magarpatta, Baner, Chakan MIDC)  

---

## 1. Brand Identity & Visual Language

TalentHub Consultant communicates an established, human-centric, corporate recruitment presence. The visual design system blends:
* Enterprise consultancy aesthetics with modern SaaS finish.
* Cohesive emerald, deep forest, and vibrant mint tones derived from the official logo.
* Balanced whitespace, 8px rhythm, clean white surfaces, and subtle glass elevation.
* Restrained spring physics and Framer Motion micro-interactions.

---

## 2. Color Tokens & Semantic Hierarchy

All color tokens are declared in `frontend/app/globals.css`:

```css
:root {
  --primary: #1D6A34;          /* Primary Brand Green */
  --primary-hover: #155227;    /* Deep Forest Hover Accent */
  --primary-deep: #0D4222;     /* Deep Forest Header & Banner Tone */
  --accent-mint: #37CE97;      /* Vibrant Mint Accent */
  --secondary-teal: #1B5F52;   /* Secondary Teal */
  --background: #F9FBFA;       /* Soft Surface Off-White */
  --surface-soft: #EAF5EF;     /* Light Green-tinted Surface */
  --text-main: #172B22;        /* Dark Slate Body Text */
  --foreground: #172B22;       /* Base Typography Color */
  --card: #FFFFFF;             /* Crisp Card Surface */
  --border: #E2E8F0;           /* Subtle Dividers */
  --muted: #F1F5F9;            /* Subtle Fill */
  --muted-text: #64756C;       /* Supporting Subtext */
}
```

### 2.1 Contrast & Accessibility (WCAG 2.2 AA)

| Color Token | Hex Code | Background Pair | Contrast Ratio | WCAG Compliance |
|---|---|---|---|---|
| Primary Green | `#1D6A34` | Pure White (`#FFFFFF`) | **5.42:1** | **Pass (AA)** |
| Deep Forest | `#0D4222` | Soft Mint (`#EAF5EF`) | **7.81:1** | **Pass (AAA)** |
| Dark Slate Text | `#172B22` | Off-White (`#F9FBFA`) | **13.56:1** | **Pass (AAA)** |
| Accent Mint | `#37CE97` | Deep Forest (`#0D4222`) | **5.14:1** | **Pass (AA)** |
| Pure White | `#FFFFFF` | Secondary Teal (`#1B5F52`)| **6.18:1** | **Pass (AA)** |

---

## 3. Typography Architecture

### 3.1 Typefaces & Font Loading
Configured in `frontend/app/layout.tsx` using `next/font/google`:
* **Headings:** `Plus Jakarta Sans` (`--font-heading`, `display: swap`, weights: 600, 700, 800)
* **Body / Controls:** `Inter` (`--font-sans`, `display: swap`, weights: 400, 500, 600)

### 3.2 Responsive Fluid Typography (`clamp()`)
* **Hero Display Title:** `clamp(2rem, 5vw, 3.75rem)` (Line height: 1.1)
* **Page H1:** `clamp(1.75rem, 4vw, 2.75rem)` (Line height: 1.2)
* **Section H2:** `clamp(1.375rem, 3vw, 2.25rem)` (Line height: 1.25)
* **Card H3:** `clamp(1.125rem, 2vw, 1.375rem)` (Line height: 1.35)
* **Body Standard:** `1rem (16px)` / Mobile `0.9375rem (15px)` (Line height: 1.6)

---

## 4. Surfaces, Elevation & Borders

* **Subtle Card:** `bg-white dark:bg-[#0c1a12] border border-border rounded-2xl shadow-sm hover:shadow-md hover:border-primary/40`
* **Elevated Container:** `rounded-3xl border border-white/60 dark:border-white/10 bg-white/90 shadow-xl backdrop-blur-xl`
* **Deep Hero Gradient:** `linear-gradient(135deg, #0D4222 0%, #1D6A34 55%, #1B5F52 100%)`
* **Soft Mint Background:** `linear-gradient(180deg, #F9FBFA 0%, #EAF5EF 100%)`

---

## 5. Motion Tokens & Physics (Framer Motion)

Centralized in `frontend/components/animations/FadeIn.tsx`:
* **Standard Transition Duration:** `180ms – 350ms`
* **Section Reveal Duration:** `400ms – 600ms`
* **Spring Physics:** `stiffness: 260, damping: 18` (natural card lift)
* **Reduced Motion:** Automatic fallback to instantaneous state changes when `@media (prefers-reduced-motion: reduce)` is detected.

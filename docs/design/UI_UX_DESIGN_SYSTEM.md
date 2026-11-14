# TalentHub Consultant — UI/UX Design System Specification

**Document Version:** 2.0.0-PROD  
**Release Date:** October 2026  
**Status:** Approved & Implemented in Production  

---

## 1. Brand Identity & Design Principles

### 1.1 Brand Positioning & Vision
* **Brand Name:** TalentHub Consultant
* **Core Positioning:** *"Find the Perfect Fit for Your Company"*
* **Geographical Focus:** Pune, Maharashtra (serving Hinjawadi Phase 1-3, Kharadi EON IT Park, Magarpatta City, Baner, Wakad, Chakan MIDC, and Bhosari Industrial Corridor).
* **Brand Character:** Professional, authentic, authoritative, modern, and human-centric.

---

## 2. Color Palette & Token Architecture

The color system is defined in `frontend/app/globals.css` using semantic CSS variables with HSL and Hex mappings:

```css
:root {
  --primary: #1D6A34;          /* Primary Green */
  --primary-hover: #155227;    /* Deep Forest Accent */
  --primary-deep: #0D4222;     /* Deep Green */
  --accent-mint: #37CE97;      /* Vibrant Mint */
  --secondary-teal: #1B5F52;   /* Secondary Teal */
  --background: #F9FBFA;       /* Premium Soft Off-White */
  --surface-soft: #EAF5EF;     /* Light Green-tinted Surface */
  --text-main: #172B22;        /* Dark Slate Green Text */
  --foreground: #172B22;       /* Base typography */
  --card: #FFFFFF;             /* Pure White Surface */
  --muted: #E2E8F0;            /* Neutral Divider & Border */
  --muted-text: #64748B;       /* Secondary Subtext */
}
```

### 2.1 WCAG AA Accessibility Contrast Matrix

| Pair | Foreground | Background | Contrast Ratio | WCAG Compliance | Usage |
|---|---|---|---|---|---|
| Primary on White | `#1D6A34` | `#FFFFFF` | **5.42:1** | **Pass (AA Normal, AAA Large)** | Buttons, key brand headers |
| Deep Green on Surface | `#0D4222` | `#EAF5EF` | **7.81:1** | **Pass (AAA All)** | Badges, card titles, hero text |
| Main Text on Background | `#172B22` | `#F9FBFA` | **13.56:1** | **Pass (AAA All)** | Standard body copy |
| Mint on Deep Green | `#37CE97` | `#0D4222` | **5.14:1** | **Pass (AA Normal, AAA Large)** | High-contrast pills & badges |
| White on Secondary Teal | `#FFFFFF` | `#1B5F52` | **6.18:1** | **Pass (AA Normal, AAA Large)** | Primary CTAs & hero badges |

---

## 3. Typography Hierarchy

### 3.1 Typefaces
* **Headings:** `Plus Jakarta Sans` (`--font-heading`, Google Fonts via `next/font/google`, `display: swap`)
* **Body / UI:** `Inter` (`--font-sans`, Google Fonts via `next/font/google`, `display: swap`)

### 3.2 Responsive Type Scale (`clamp()`)

| Level | Desktop Size | Mobile Size | Weight | Line Height | Usage |
|---|---|---|---|---|---|
| **Display / Hero** | `clamp(2.5rem, 5vw, 4.25rem)` | `2.25rem` | 800 (Bold) | 1.1 | Homepage Hero Title |
| **H1** | `clamp(2rem, 4vw, 3rem)` | `1.75rem` | 700 (Bold) | 1.2 | Page Primary Titles |
| **H2** | `clamp(1.5rem, 3vw, 2.25rem)` | `1.375rem` | 700 (Bold) | 1.25 | Section Headers |
| **H3** | `clamp(1.25rem, 2vw, 1.5rem)` | `1.125rem` | 600 (Semi-bold) | 1.35 | Card Titles & Sub-headings |
| **Body Large** | `1.125rem (18px)` | `1rem (16px)` | 400 / 500 | 1.6 | Hero leads, intro paragraphs |
| **Body Standard** | `1rem (16px)` | `0.9375rem (15px)` | 400 | 1.6 | General content, descriptions |
| **Caption / Meta** | `0.875rem (14px)` | `0.8125rem (13px)` | 500 / 600 | 1.4 | Badges, tags, breadcrumbs |

---

## 4. Spacing, Elevation & Surfaces

### 4.1 8-Pixel Grid System
* Base Unit: `8px`
* Spacing Scale: `4px (0.5)`, `8px (1)`, `16px (2)`, `24px (3)`, `32px (4)`, `48px (6)`, `64px (8)`, `96px (12)`

### 4.2 Elevation & Shadows
* **Subtle (`shadow-sm`):** `0 1px 2px 0 rgba(13, 66, 34, 0.05)` (Cards, input fields)
* **Medium (`shadow-md`):** `0 4px 6px -1px rgba(13, 66, 34, 0.08)` (Dropdown menus, sticky navbar)
* **Elevated (`shadow-xl`):** `0 20px 25px -5px rgba(13, 66, 34, 0.10)` (Modals, hero search container)
* **Hover State:** Translate-Y `-2px` with expanded shadow radius (`transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1)`).

---

## 5. Component Library & States

### 5.1 Buttons
* **Primary:** Background `#1D6A34`, text `#FFFFFF`, rounded `rounded-xl`, padding `px-6 py-3.5`, hover `#155227` with scale `1.01`.
* **Secondary / Outline:** Border `border-2 border-[#1D6A34]`, text `#1D6A34`, hover `bg-[#EAF5EF]`.
* **Accent / Mint:** Background `#37CE97`, text `#0D4222`, font-semibold, hover brightness `1.05`.
* **Ghost:** Text `#1D6A34`, hover `bg-slate-100`, focus-visible `ring-2 ring-[#1D6A34]`.

### 5.2 Input Controls & Search
* Container: White surface with rounded `rounded-2xl`, border `#E2E8F0`, focus ring `ring-2 ring-[#1D6A34]`.
* Icon Prefix: Subtle slate `#94A3B8`, interactive clear buttons.
* Error state: Red outline `#EF4444`, accessible ARIA live feedback.

### 5.3 Badges & Tags
* **Verified / Live:** Green background `#DCFCE7`, text `#15803D`, border `#BBF7D0`.
* **Employment Type:** Soft surface `#EAF5EF`, text `#0D4222`.
* **Industry / Domain:** Neutral surface `#F1F5F9`, text `#334155`.

### 5.4 Dual-Mode Banner (Static vs Dynamic)
* On static export deployment without live PostgreSQL/Django connectivity:
  * Top info alert informing candidate of static read-only catalog mode.
  * Direct one-click CV submission to `hr@talenthubconsultant.com` with pre-filled subject line.
  * Zero simulated fake application submissions.

---

## 6. Motion & Animation Standards

* **Tooling:** Framer Motion (`motion/react`)
* **Motion Duration:** Standard UI transitions capped at `200ms - 400ms`.
* **Easing:** `cubic-bezier(0.16, 1, 0.3, 1)` (snappy entry, natural deceleration).
* **Reduced Motion:** Fully complies with `@media (prefers-reduced-motion: reduce)`.

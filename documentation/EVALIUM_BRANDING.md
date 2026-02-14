# Evalium - Brand Guidelines

## Name

**Evalium** — a fusion of *Evaluation* and the Latin suffix *-ium* (denoting a fundamental element).

### Rationale

| Criterion         | Detail                                                                 |
|-------------------|------------------------------------------------------------------------|
| **Meaning**       | "Eval-" immediately conveys assessment/evaluation                      |
| **Suffix "-ium"** | Evokes a chemical element, suggesting something foundational and pure  |
| **Bilingual**     | Works identically in English and French with no adaptation needed      |
| **Brevity**       | 7 letters, 3 syllables, easy to spell, say, and remember              |
| **Uniqueness**    | Distinctive name that stands out in the EdTech space                   |
| **Domain**        | Short enough for clean URLs: evalium.app, evalium.io                   |

### Pronunciation

`/e.va.ljom/` (FR) | `/ɪˈvæl.i.əm/` (EN)

---

## Slogan

> **Where every grade tells a story.**

The slogan emphasizes that Evalium goes beyond simple scoring. Each assessment, each grade, each review is a meaningful data point in a student's learning journey.

---

## Logo

### Concept: The Prism

The logo is a **faceted gem/prism** seen from above, composed of four geometric faces:

- **Top face** (crown): light enters from above, representing input (questions, assessments)
- **Left face**: deeper indigo, representing analysis and rigor
- **Right face**: medium indigo, representing clarity and insight
- **Convergence point** (bottom vertex): all facets converge, symbolizing the synthesized result (the grade)

A small **sparkle** on the upper-left facet adds life and suggests precision.

### Why a Prism?

A prism takes a single beam of light and decomposes it into a spectrum of colors. Similarly, Evalium takes raw assessment data and decomposes it into actionable insights: scores, statistics, progress tracking. The prism also reinforces the "-ium" element metaphor (crystalline, elemental, precise).

### Logo Variants

| Variant        | File                        | Use Case                                |
|----------------|-----------------------------|-----------------------------------------|
| **Favicon**    | `public/favicon.svg`        | Browser tab, bookmarks, PWA icon        |
| **Horizontal** | `public/logo-evalium.svg`   | Header, emails, documents, login page   |
| **React Icon** | `Components/layout/Logo.tsx`| In-app usage (className-based sizing)   |
| **React Full** | `Components/shared/LogoEvalium.tsx` | Sidebar (width/height props)    |

---

## Color Palette

### Primary Colors

| Name             | Hex       | Tailwind      | Role                                    |
|------------------|-----------|---------------|-----------------------------------------|
| **Indigo 900**   | `#312e81` | `indigo-900`  | Headings, dark text, logo wordmark      |
| **Indigo 600**   | `#4f46e5` | `indigo-600`  | Primary actions, buttons, active states |
| **Indigo 500**   | `#6366f1` | `indigo-500`  | Secondary actions, links, hover states  |
| **Indigo 400**   | `#818cf8` | `indigo-400`  | Highlights, accents, gem crown          |

### Supporting Colors

| Name             | Hex       | Tailwind      | Role                                    |
|------------------|-----------|---------------|-----------------------------------------|
| **Indigo 800**   | `#3730a3` | `indigo-800`  | Gem shadow, dark mode primary           |
| **Indigo 200**   | `#c7d2fe` | `indigo-200`  | Subtle backgrounds, disabled states     |
| **Indigo 100**   | `#e0e7ff` | `indigo-100`  | Card backgrounds, hover surfaces        |
| **Indigo 50**    | `#eef2ff` | `indigo-50`   | Page backgrounds, light surfaces        |

### Semantic Colors (unchanged)

| Purpose    | Color      | Hex       |
|------------|------------|-----------|
| Success    | Green 600  | `#16a34a` |
| Warning    | Amber 500  | `#f59e0b` |
| Error      | Red 600    | `#dc2626` |
| Info       | Blue 500   | `#3b82f6` |

### Gradient Definitions

```css
/* Gem top face */
background: linear-gradient(135deg, #818cf8, #6366f1);

/* Gem left face */
background: linear-gradient(180deg, #4f46e5, #3730a3);

/* Gem right face */
background: linear-gradient(180deg, #6366f1, #4f46e5);

/* Hero / CTA gradient */
background: linear-gradient(135deg, #4f46e5, #7c3aed);
```

---

## Typography

| Element        | Font                                          | Weight | Size   |
|----------------|-----------------------------------------------|--------|--------|
| Logo wordmark  | Segoe UI / system-ui / -apple-system          | 700    | 28px   |
| Slogan         | Segoe UI / system-ui / -apple-system (italic) | 400    | 11px   |
| Headings       | Inter / system font stack                     | 600-700| 18-32px|
| Body text      | Inter / system font stack                     | 400    | 14-16px|
| Code           | JetBrains Mono / monospace                    | 400    | 13px   |

---

## Logo Usage Rules

### Do

- Maintain minimum clear space equal to the gem height around the logo
- Use on white, light gray (`#f9fafb`), or indigo-50 (`#eef2ff`) backgrounds
- Scale proportionally (never stretch or distort)

### Do Not

- Place on busy or patterned backgrounds
- Rotate or skew the gem
- Change the gradient colors
- Add drop shadows or outer glows

---

## Application

### Favicon

The gem prism alone, 48x48 viewBox, serves as the browser favicon and app icon.

### Sidebar (Expanded)

Gem icon + "Evalium" text label from `sidebar.app_name` translation key.

### Sidebar (Collapsed)

Gem icon only at 32x32.

### Login Page

Centered gem icon at default 48x48 size via the `<Logo />` component.

### Email Notifications

Horizontal logo variant with wordmark and slogan.

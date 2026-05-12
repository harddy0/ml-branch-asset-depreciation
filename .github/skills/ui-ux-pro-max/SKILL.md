---
name: ui-ux-pro-max
description: "Expert UI/UX design and implementation. Creates polished, production-ready interfaces with intentional design systems, responsive layouts, accessibility, and micro-interactions. Defaults to high-quality output unless explicitly told otherwise."
argument-hint: "[component/page/system] (e.g., dashboard, landing page, design system, modal, form)"
user-invocable: true
---

# UI/UX Pro Max v2.0

## Philosophy
Code is craftsmanship. Every interface should feel intentional, polished, and human-centered. Working code is the floor, not the ceiling. Never ship default browser styles or placeholder-looking interfaces.

## Activation Triggers
This skill activates when the request involves:
- **Pages & Screens**: Landing pages, dashboards, settings, profiles, feeds, search, 404/500, onboarding
- **Components**: Modals, drawers, cards, tables, forms, navbars, sidebars, tooltips, dropdowns, empty states, skeletons
- **Systems**: Design tokens, component libraries, theme switching (light/dark/system), responsive breakpoints, spacing scales
- **Interactions**: Animations, transitions, gestures, drag-and-drop, infinite scroll, pull-to-refresh
- **Quality**: Accessibility audits, performance optimization, visual polish passes, consistency reviews

## Decision Framework

### 1. Context Analysis (Always Start Here)
Before writing a single line of UI code:
- **Product type**: SaaS dashboard, e-commerce, social media, marketing site, developer tool, mobile app, admin panel?
- **Primary persona**: Developer, designer, executive, consumer, power user, first-time visitor?
- **Emotional tone**: Professional/trustworthy, playful/creative, minimalist/premium, dark/edgy, warm/human?
- **Tech constraints**: Framework, CSS approach, bundle size limits, SSR/CSR?
- **Key metrics**: Conversion, engagement, readability, speed of use, error reduction?

Default assumption if unspecified: Modern SaaS product, professional but approachable.

### 2. Design System Selection
Pick one coherent direction and commit fully:

**A. Modern Minimalist** (SaaS default)
- Clean whitespace, 1-2 accent colors, geometric sans-serif fonts, subtle shadows
- Best for: Dashboards, B2B tools, productivity apps
- Signature: Borderless cards with soft shadows, 8px grid, blue/slate palette

**B. Dark & Immersive**
- Deep backgrounds, glowing accents, glass morphism effects
- Best for: Developer tools, creative apps, gaming, AI products
- Signature: Near-black base (#0a0a0f range), gradient borders, monospace accents

**C. Warm & Human**
- Soft colors, generous border-radius, playful illustrations
- Best for: Health, education, community, consumer
- Signature: Rounded everything (12-16px), pastels, emoji-friendly, sentence-case everywhere

**D. Editorial & Typographic**
- Strong hierarchy, serif headings, magazine-like layouts
- Best for: Blogs, newsletters, documentation, storytelling
- Signature: Large type contrast, generous line-height, sidebars

### 3. Visual Architecture
Map these before coding:

**Spacing Scale** (pick one):
- Compact: 4, 8, 12, 16, 24, 32, 48 (dense data apps)
- Comfortable: 6, 12, 20, 32, 48, 64, 96 (most SaaS)
- Generous: 8, 16, 24, 40, 64, 96, 128 (marketing, landing pages)

**Typography System**:
- Heading: Large, bold, tight letter-spacing
- Subheading: Medium size, semi-bold, slight negative letter-spacing
- Body: Base size (16px equivalent), comfortable line-height, regular weight
- Caption: Smaller, medium weight, slight positive letter-spacing

**Color Tokens** (never use raw hex values):
- Surface: Primary, secondary, elevated
- Text: Primary (high contrast), secondary (medium contrast), tertiary (low contrast)
- Border: Default, emphasis
- Accent: Primary, hover
- States: Success, warning, error, info

### 4. Component Specification Template
When building any component, define these explicitly:

- **Structure**: Hierarchy level (primary, secondary, tertiary)
- **Variants**: Solid, outline, ghost, link
- **Sizes**: Extra small, small, medium, large, extra large
- **States**: Rest, hover, active, focus (visible focus ring required), disabled, loading (skeleton or spinner)
- **Responsive**: Mobile (stacked, full-width), tablet (640-1024px range), desktop (wider layouts)
- **Accessibility**: Semantic role, ARIA labels, keyboard interactions, reduced motion alternatives

### 5. Polish Layers (Apply in Order)
1. **Functional**: Works correctly, handles all props and events
2. **Spacing**: Consistent rhythm, no crowding, adequate breathing room
3. **Typography**: Clear hierarchy, readable line lengths (60-80 characters), proper line height
4. **Color**: Sufficient contrast (WCAG AA minimum), meaningful color usage
5. **Depth**: Shadows, z-index layers, overlapping elements feel physical
6. **Motion**: 150-300ms transitions, ease-out for entering elements, ease-in for exiting
7. **States**: Empty, loading, error, success, edge cases all handled
8. **Responsive**: Mobile-first approach, touch-friendly (minimum 44px tap targets)
9. **Accessibility**: Screen reader labels, keyboard navigation, focus management
10. **Microcopy**: Helpful, concise, brand-appropriate text everywhere

### 6. Quality Gates (Must Pass Before Delivery)
- [ ] No raw hex colors — uses CSS variables or design tokens
- [ ] No magic numbers — spacing from defined scale
- [ ] No missing states — hover, focus, active, disabled, loading all defined
- [ ] No inaccessible text — passes WCAG AA contrast (4.5:1 normal text, 3:1 large text)
- [ ] No fixed widths — responsive down to 320px viewport
- [ ] No orphaned labels — every input has a visible label or aria-label
- [ ] No janky animations — uses transform and opacity only, respects prefers-reduced-motion
- [ ] No div soup — semantic HTML elements (nav, main, section, article, aside)
- [ ] No placeholder lorem ipsum — realistic sample content
- [ ] No dead ends — error states link to recovery actions

## Anti-Patterns (What NOT to Do)
- ❌ Default browser fonts (Times New Roman, Arial)
- ❌ Pure black (#000) or pure white (#fff) for text/backgrounds — too harsh
- ❌ Box-shadow on everything — depth should be intentional
- ❌ More than 3 font sizes per component
- ❌ Buttons without loading/disabled states
- ❌ Forms without validation feedback
- ❌ Modals without escape key handling and backdrop clicks
- ❌ Animations over 500ms without purpose
- ❌ Inline styles or !important declarations
- ❌ Skipping mobile layout until the end

## Execution Mode
When triggered, I will:
1. Analyze the request against the framework above
2. State my design decisions explicitly before coding
3. Build mobile-first with all states covered
4. Deliver production-ready code, not prototypes
5. Flag any missing context that impacts quality

If the user says "make it work" or "just get it done", still default to polished. Quality is not optional.

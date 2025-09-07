# AI Section Builder - Critical Architecture Documentation

## 🚨 IMPORTANT: Read This First for Every Session

This document contains critical architectural decisions and rules that MUST be followed to maintain consistency and prevent issues. This is your single source of truth for development.

---

## CSS Architecture - CRITICAL RULES

### File Structure and Responsibilities

```
/assets/css/
├── core/                    # Shared design system components
│   ├── 00-tokens.css       # CSS variables and design tokens
│   ├── 01-reset.css        # CSS reset/normalize
│   └── 02-utilities.css    # Reusable utility classes
├── sections/                # Section-specific styles (ONE FILE PER SECTION)
│   ├── hero.css            # Hero section ONLY
│   ├── features.css        # Features section ONLY
│   ├── testimonials.css    # Testimonials section ONLY
│   └── [section-name].css  # Each section isolated
├── editor/
│   └── editor-ui.css       # Editor UI ONLY (toolbar, panels, sidebars)
└── shared/
    ├── _variables.css      # Legacy - being migrated to core/00-tokens.css
    └── hero-core.css       # Current hero styles - will move to sections/hero.css
```

### 🔴 NEVER DO THIS

1. **NEVER override section styles in editor CSS**
   - `editor-ui.css` must NEVER contain styles for `.aisb-hero`, `.aisb-features`, etc.
   - Editor CSS is ONLY for the editor interface (toolbar, panels, sidebars)

2. **NEVER use undefined CSS variables**
   - Bad: `color: var(--color-primary);` (if --color-primary isn't defined)
   - Good: `color: #2563eb;` or `color: var(--aisb-primary, #2563eb);`

3. **NEVER mix editor UI with section styles**
   - Section styles go in `/sections/`
   - Editor UI styles go in `/editor/`
   - Never shall they meet

4. **NEVER create dependencies between sections**
   - Each section CSS file must be completely independent
   - No shared classes between different section types

### ✅ ALWAYS DO THIS

1. **One CSS file per section type**
   - `sections/hero.css` contains ALL styles for hero sections
   - `sections/features.css` contains ALL styles for features sections

2. **Use BEM naming convention**
   ```css
   .aisb-hero {}           /* Block */
   .aisb-hero__heading {}  /* Element */
   .aisb-hero--dark {}     /* Modifier */
   ```

3. **Test in both editor and frontend**
   - Styles must render identically in both contexts
   - Use the same CSS file for both

4. **Use CSS custom properties with fallbacks**
   ```css
   background: var(--aisb-hero-bg, #ffffff);
   color: var(--aisb-hero-text, #1a1a1a);
   ```

---

## PHP Enqueuing Strategy

### Frontend (Performance Optimized)
```php
// Only load styles for sections actually used on the page
$sections = get_post_meta($post_id, '_aisb_sections', true);
foreach ($sections as $section) {
    wp_enqueue_style(
        'aisb-section-' . $section['type'],
        AISB_PLUGIN_URL . 'assets/css/sections/' . $section['type'] . '.css',
        [],
        AISB_VERSION
    );
}
```

### Editor (Everything Available)
```php
// Load shared hero-core.css for all section styles
wp_enqueue_style(
    'aisb-hero-core',
    AISB_PLUGIN_URL . 'assets/css/shared/hero-core.css',
    [],
    AISB_VERSION
);

// Load editor UI separately - NEVER includes section styles
wp_enqueue_style(
    'aisb-editor-ui',
    AISB_PLUGIN_URL . 'assets/css/editor/editor-ui.css',
    ['aisb-hero-core'], // Depends on section styles
    AISB_VERSION
);
```

---

## Current Issues Being Fixed

### Button Styling Inconsistency (Fixed)
- **Problem**: Editor CSS was overriding button styles with undefined variables
- **Solution**: Removed ALL button styles from editor-ui.css, now using hero-core.css only
- **Location**: Button styles are in `assets/css/shared/hero-core.css` lines 311-383

### Media Fields Not Persisting (Fixed)
- **Problem**: Migration function was deleting media_type and video_url fields
- **Solution**: Modified `aisb_migrate_field_names()` to preserve these fields

---

## Design System Values

### Colors (8-color system)
```css
/* Light Mode */
--color-base: #ffffff;      /* White */
--color-text: #1a1a1a;      /* Near black */
--color-muted: #64748b;     /* Slate gray */
--color-primary: #2563eb;   /* Blue */

/* Dark Mode */
--color-dark-base: #1a1a1a;     /* Near black */
--color-dark-text: #fafafa;     /* Near white */
--color-dark-muted: #9ca3af;    /* Gray */
--color-dark-primary: #60a5fa;  /* Light blue */
```

### Spacing (8px grid)
```css
--space-xs: 8px;
--space-sm: 16px;
--space-md: 24px;
--space-lg: 32px;
--space-xl: 48px;
--space-2xl: 64px;
--space-3xl: 96px;
```

---

## Testing Checklist

Before marking any CSS work as complete:

- [ ] Buttons appear correctly in light mode (blue background for primary)
- [ ] Buttons appear correctly in dark mode (light blue for primary)
- [ ] Secondary buttons have transparent background with border
- [ ] Styles are identical in editor preview and frontend
- [ ] No console errors about undefined CSS variables
- [ ] Media (images/videos) display correctly in both contexts

---

## Migration Plan

### Current State (December 2024)
- `/assets/css/sections/hero.css` contains hero section styles
- `/assets/css/core/00-tokens.css` contains ALL design tokens (including editor variables)
- `/assets/css/core/02-utilities.css` contains shared components (buttons, etc.)
- `editor-styles.css` imports tokens for editor UI variables (required!)
- Button overrides removed from editor-styles.css

### Target State
- Each section has its own CSS file in `/sections/`
- Editor UI completely separated from section styles
- Dynamic CSS loading based on page content

### Do NOT create new files unless explicitly needed for new sections

---

## For New Sessions

1. **Read this entire document first**
2. **Check current file structure** - don't assume
3. **Never override section styles in editor CSS**
4. **Test in both editor and frontend**
5. **Update this document** if architecture changes

---

## Commands to Remember

```bash
# Check for undefined CSS variables
grep -r "var(--" assets/css/ | grep -v ":root"

# Find section style overrides in wrong files
grep -r "\.aisb-hero\|\.aisb-features" assets/css/editor/

# Test button rendering
# 1. Open editor with a hero section
# 2. Toggle between light and dark modes
# 3. Check both primary and secondary buttons
# 4. View the same page on frontend
# 5. Styles should be identical
```

---

Last Updated: December 2024
Critical for: All future development sessions
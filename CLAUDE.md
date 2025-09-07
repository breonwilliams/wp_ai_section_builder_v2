# AI Section Builder - Critical Architecture Documentation

## 🚨 IMPORTANT: Read This First for Every Session

This document contains critical architectural decisions and rules that MUST be followed to maintain consistency and prevent issues. This is your single source of truth for development.

---

## CSS Architecture - CRITICAL RULES

### File Structure and Responsibilities (PRODUCTION READY)

```
/assets/css/
├── core/                    # Single source of truth for design system
│   ├── 00-tokens.css       # ALL CSS variables (section + editor tokens)
│   └── 02-utilities.css    # Shared components (buttons, etc.)
├── sections/                # Section-specific styles (ONE FILE PER SECTION)
│   └── hero.css            # Hero section - imports core tokens & utilities
├── editor/
│   └── editor-styles.css   # Editor UI ONLY - imports core tokens
└── admin/
    ├── admin-styles.css    # Admin interface - imports core tokens
    └── components/
        └── _buttons.css    # Admin-specific button styles
```

### 🟢 PRODUCTION READY STATUS
- ✅ Legacy `/shared/` directory DELETED (duplicates removed)
- ✅ Legacy `/design-system/` directory DELETED (duplicates removed)
- ✅ Single source of truth established for all styles
- ✅ All imports updated to use `core/00-tokens.css`
- ✅ No undefined CSS variables
- ✅ No duplicate button styles
- ✅ Editor dark theme restored and working
- ✅ Frontend viewport overflow fixed

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

## PHP Enqueuing Strategy (UPDATED FOR PRODUCTION)

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

### Editor (PRODUCTION READY)
```php
// Load hero section styles (includes core tokens + utilities via @import)
wp_enqueue_style(
    'aisb-hero-section',
    AISB_PLUGIN_URL . 'assets/css/sections/hero.css',
    [],
    AISB_VERSION
);

// Load editor UI separately - imports core/00-tokens.css for editor variables
wp_enqueue_style(
    'aisb-editor-ui',
    AISB_PLUGIN_URL . 'assets/css/editor/editor-styles.css',
    [],
    AISB_VERSION
);
```

---

## Issues Fixed in Production Cleanup

### ✅ Button Styling Inconsistency (RESOLVED)
- **Problem**: Editor CSS was overriding button styles with undefined variables
- **Solution**: Removed duplicate button styles, single source in `core/02-utilities.css`
- **Admin buttons**: Separate styles in `admin/components/_buttons.css` (different design)

### ✅ Editor Dark Theme (RESOLVED)
- **Problem**: Editor UI lost all styling when shared directory was removed
- **Solution**: All editor variables added to `core/00-tokens.css`, editor imports this

### ✅ Frontend Viewport Overflow (RESOLVED)
- **Problem**: Hero section causing horizontal scroll with `width: 100vw` hack
- **Solution**: Removed viewport hack, using proper container with `max-width: 1200px`

### ✅ Duplicate Files Cleanup (RESOLVED)
- **Problem**: Multiple directories with duplicate styles causing confusion
- **Solution**: Deleted `/shared/` and `/design-system/` directories entirely

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

### ✅ PRODUCTION READY STATE (January 2025)
- `/assets/css/core/00-tokens.css` - Single source of truth for ALL CSS variables
- `/assets/css/core/02-utilities.css` - Shared components (buttons, etc.)
- `/assets/css/sections/hero.css` - Hero section styles (imports core files)
- `/assets/css/editor/editor-styles.css` - Editor UI only (imports tokens)
- `/assets/css/admin/admin-styles.css` - Admin interface (imports tokens)
- All duplicate directories deleted (`/shared/`, `/design-system/`)
- All imports updated to use single source files
- No undefined CSS variables
- No duplicate button styles

### ⚠️ CRITICAL: Do NOT recreate deleted directories
The `/shared/` and `/design-system/` directories have been permanently deleted to eliminate confusion. All styles are now properly organized in the structure above.

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
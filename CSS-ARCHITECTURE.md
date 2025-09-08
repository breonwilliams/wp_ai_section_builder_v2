# AI Section Builder - CSS Architecture Documentation

## Overview
This plugin uses a **context-aware CSS architecture** that enables automatic theme adaptation without duplicate code. All sections automatically support light/dark modes through CSS variable inheritance.

## Architecture Layers

### 1. Design Tokens (`00-tokens.css`)
The foundation layer containing all raw color values and design decisions.

```css
/* Raw color values - DO NOT use directly in components */
--aisb-color-primary: #2563eb;
--aisb-color-dark-primary: #60a5fa;

/* Semantic tokens - Maps colors to their purpose */
--aisb-interactive-primary: var(--aisb-color-primary);
--aisb-content-primary: var(--aisb-color-text);
```

### 2. Base Architecture (`01-base.css`)
Establishes the inheritance pattern for all sections.

```css
.aisb-section {
    /* Context variables that change based on theme */
    --section-bg: var(--aisb-surface-primary);
    --section-text: var(--aisb-content-primary);
    --section-link: var(--aisb-content-link);
}

.aisb-section--light {
    /* Updates context variables for light mode */
    --section-bg: var(--aisb-color-base);
    --section-text: var(--aisb-color-text);
}

.aisb-section--dark {
    /* Updates context variables for dark mode */
    --section-bg: var(--aisb-color-dark-base);
    --section-text: var(--aisb-color-dark-text);
}
```

### 3. Utilities (`02-utilities.css`)
Reusable components that automatically adapt to their context.

```css
.aisb-btn-primary {
    /* Uses context variables, NOT raw colors */
    background: var(--section-button-primary);
    color: var(--section-button-primary-text);
}
/* NO duplicate dark mode rules needed! */
```

### 4. Section Styles (`sections/*.css`)
Individual section implementations that inherit from the base.

```css
.aisb-hero__heading {
    /* Automatically correct in both themes */
    color: var(--section-text);
}
```

## Creating New Sections

### Step 1: Create Section File
```css
/* sections/new-section.css */
@import url('../core/00-tokens.css');
@import url('../core/01-base.css');
@import url('../core/02-utilities.css');

.aisb-new-section {
    /* Will inherit from .aisb-section base */
}
```

### Step 2: Use Context Variables
```css
.aisb-new-section__title {
    color: var(--section-text);  /* Automatically adapts */
}

.aisb-new-section__link {
    color: var(--section-link);  /* Correct in both themes */
}
```

### Step 3: Apply Theme Classes
```html
<!-- Light theme -->
<section class="aisb-section aisb-section--light">
    <div class="aisb-new-section">...</div>
</section>

<!-- Dark theme -->
<section class="aisb-section aisb-section--dark">
    <div class="aisb-new-section">...</div>
</section>
```

## Important Rules

### ✅ DO:
- Use context variables (`--section-*`) for all colors
- Import base architecture files in order
- Apply `.aisb-section` with theme modifier
- Use semantic tokens for new variables

### ❌ DON'T:
- Use hardcoded hex colors (`#2563eb`)
- Create duplicate dark mode CSS rules
- Use raw color tokens directly in components
- Mix editor UI colors with section colors

## Variable Reference

### Context Variables (Use These!)
| Variable | Purpose | Example |
|----------|---------|---------|
| `--section-bg` | Section background | `background: var(--section-bg)` |
| `--section-text` | Primary text | `color: var(--section-text)` |
| `--section-text-secondary` | Muted/secondary text | `color: var(--section-text-secondary)` |
| `--section-link` | Link color | `color: var(--section-link)` |
| `--section-link-hover` | Link hover state | `color: var(--section-link-hover)` |
| `--section-button-primary` | Primary button bg | `background: var(--section-button-primary)` |
| `--section-border` | Border color | `border-color: var(--section-border)` |
| `--section-focus-ring` | Focus outline | `outline-color: var(--section-focus-ring)` |

### How Theme Switching Works

1. Base `.aisb-section` class defines context variables with defaults
2. Theme modifiers (`--light`, `--dark`) override these variables
3. Components use context variables, inheriting the correct values
4. Result: No duplicate CSS, automatic theme adaptation

## Testing

Use `/tests/architecture-test.html` to verify:
- Context variables are working
- Theme switching updates all colors
- No hardcoded colors remain
- Buttons and links adapt correctly

## Benefits

1. **No Duplicate Code**: Write styles once, work in all themes
2. **Automatic Adaptation**: Components inherit correct colors
3. **Maintainable**: Single source of truth for colors
4. **Scalable**: New sections automatically support themes
5. **Consistent**: Enforced design system across all sections

## Migration Guide (For Existing Sections)

### Before (Old Pattern):
```css
.my-component {
    color: #2563eb;  /* Hardcoded */
}

.aisb-section--dark .my-component {
    color: #60a5fa;  /* Duplicate dark mode rule */
}
```

### After (New Pattern):
```css
.my-component {
    color: var(--section-link);  /* Automatic adaptation */
}
/* No dark mode rule needed! */
```

## Common Pitfalls

1. **Using wrong variables**: Always use `--section-*` variables, not `--aisb-color-*`
2. **Forgetting imports**: Must import base architecture files
3. **Missing base class**: Sections need `.aisb-section` class
4. **Hardcoded colors**: Never use hex values directly

## Questions?

If you're unsure about implementation:
1. Check existing sections (hero.css) for examples
2. Run the architecture test to verify
3. Use browser DevTools to inspect computed variables
4. Ensure all colors come from context variables
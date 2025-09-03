# Global Settings Implementation Guide

## Overview
This document outlines the best practice implementation for global settings in the AI Section Builder, based on research of industry-leading page builders (Elementor and Breakdance).

## Architecture Pattern

### CSS Variables Approach (Industry Standard)
Both Elementor and Breakdance use CSS custom properties (variables) as the foundation for their global settings system. This approach provides:
- **Instant updates** without page reload
- **Browser-native performance**
- **Cascade inheritance** for consistent styling
- **Runtime theming** capabilities

## Implementation Structure

### 1. Database Storage
```php
// Single option for all global settings
$global_settings = [
    'colors' => [
        'primary'    => '#667EEA',
        'secondary'  => '#764BA2',
        'text'       => '#333333',
        'heading'    => '#1A1A1A',
        'background' => '#FFFFFF',
        'accent'     => '#F97316'
    ],
    'typography' => [
        'heading_font'  => 'Inter',
        'body_font'     => 'System UI',
        'heading_scale' => 1.618,  // Golden ratio
        'base_size'     => 16
    ],
    'spacing' => [
        'section_padding' => '80px',
        'element_gap'     => '20px'
    ]
];

// Store as single option
update_option('aisb_global_settings', $global_settings);
```

### 2. CSS Variable Injection
```php
/**
 * Inject global CSS variables into admin head
 */
function aisb_inject_global_styles() {
    $settings = get_option('aisb_global_settings', []);
    
    $css = ':root {';
    
    // Colors
    foreach ($settings['colors'] as $name => $value) {
        $css .= sprintf('--aisb-global-%s: %s;', $name, $value);
    }
    
    // Typography
    foreach ($settings['typography'] as $name => $value) {
        $css .= sprintf('--aisb-global-%s: %s;', 
                       str_replace('_', '-', $name), $value);
    }
    
    $css .= '}';
    
    echo '<style id="aisb-global-styles">' . $css . '</style>';
}
add_action('admin_head', 'aisb_inject_global_styles');
add_action('wp_head', 'aisb_inject_global_styles');
```

### 3. Settings Panel UI Structure
```javascript
// Global Settings Manager
const GlobalSettings = {
    // State
    settings: {},
    isDirty: false,
    
    // Initialize
    init() {
        this.loadSettings();
        this.bindEvents();
        this.createPanel();
    },
    
    // Create settings panel
    createPanel() {
        return {
            colors: this.createColorSection(),
            typography: this.createTypographySection(),
            spacing: this.createSpacingSection()
        };
    },
    
    // Update CSS variables in real-time
    updateVariable(name, value) {
        document.documentElement.style.setProperty(
            `--aisb-global-${name}`, 
            value
        );
        this.settings[name] = value;
        this.isDirty = true;
        this.debouncedSave();
    },
    
    // Debounced save (500ms)
    debouncedSave: debounce(() => {
        this.saveSettings();
    }, 500)
};
```

### 4. Section Implementation Without Local Colors
```javascript
// Hero section uses global colors only
function renderHeroSection(section) {
    // No local color options - uses CSS variables
    return `
        <div class="aisb-section aisb-section-hero">
            <div class="aisb-hero-content">
                <h1 class="aisb-hero-headline">
                    ${section.content.headline}
                </h1>
                <p class="aisb-hero-subheadline">
                    ${section.content.subheadline}
                </p>
                <a href="${section.content.button_url}" 
                   class="aisb-hero-button">
                    ${section.content.button_text}
                </a>
            </div>
        </div>
    `;
}
```

### 5. CSS Using Global Variables
```css
/* Sections automatically use global colors */
.aisb-section-hero {
    background: linear-gradient(135deg, 
        var(--aisb-global-primary) 0%, 
        var(--aisb-global-secondary) 100%
    );
}

.aisb-hero-headline {
    color: white;  /* On dark backgrounds */
    font-family: var(--aisb-global-heading-font);
}

.aisb-hero-subheadline {
    color: rgba(255, 255, 255, 0.9);
    font-family: var(--aisb-global-body-font);
}

.aisb-hero-button {
    background: white;
    color: var(--aisb-global-primary);
}

/* Light sections use inverted colors */
.aisb-section-features {
    background: var(--aisb-global-background);
    color: var(--aisb-global-text);
}

.aisb-section-features h2 {
    color: var(--aisb-global-heading);
}
```

## AJAX Implementation

### Save Endpoint
```php
function aisb_ajax_save_global_settings() {
    check_ajax_referer('aisb_global_settings_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $settings = json_decode(stripslashes($_POST['settings']), true);
    
    // Validate and sanitize
    $clean_settings = [
        'colors' => array_map('sanitize_hex_color', 
                             $settings['colors'] ?? []),
        'typography' => array_map('sanitize_text_field', 
                                 $settings['typography'] ?? []),
        'spacing' => array_map('sanitize_text_field', 
                              $settings['spacing'] ?? [])
    ];
    
    update_option('aisb_global_settings', $clean_settings);
    
    wp_send_json_success([
        'message' => 'Settings saved',
        'settings' => $clean_settings
    ]);
}
```

### JavaScript AJAX Handler
```javascript
// Save settings via AJAX
saveSettings() {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aisb_save_global_settings',
            settings: JSON.stringify(this.settings),
            nonce: aisb_editor.global_nonce
        },
        success: (response) => {
            if (response.success) {
                this.isDirty = false;
                this.showNotification('Settings saved');
            }
        }
    });
}
```

## Benefits of This Approach

### 1. **User Experience**
- Single location for all color/style changes
- Prevents design inconsistency
- Instant preview of changes
- No need to edit individual sections

### 2. **Performance**
- CSS variables are browser-optimized
- No JavaScript re-rendering needed
- Cached in browser
- Minimal database queries

### 3. **Maintainability**
- Centralized style management
- Easy to add new global settings
- Clean separation of concerns
- Future-proof for theming

### 4. **Consistency**
- Enforces design system
- Prevents user errors
- Professional appearance
- Brand consistency

## Migration Path

### Phase 1: Remove Local Colors
- Remove color pickers from section forms
- Update sections to use CSS variables

### Phase 2: Implement Global Panel
- Create settings UI in toolbar
- Add color pickers for global colors
- Implement AJAX save

### Phase 3: Extend Settings
- Add typography controls
- Add spacing controls
- Add advanced settings

### Phase 4: Presets System
- Create color scheme presets
- Allow import/export
- Add reset functionality

## Future Enhancements

### 1. **Preset Themes**
```javascript
const presets = {
    'modern': {
        primary: '#667EEA',
        secondary: '#764BA2'
    },
    'corporate': {
        primary: '#0EA5E9',
        secondary: '#0284C7'
    },
    'vibrant': {
        primary: '#F97316',
        secondary: '#DC2626'
    }
};
```

### 2. **Responsive Settings**
- Different settings for mobile/tablet/desktop
- Breakpoint-specific overrides

### 3. **Custom Properties**
- Allow users to add custom CSS variables
- Advanced mode for developers

### 4. **Color Accessibility**
- WCAG compliance checking
- Contrast ratio validation
- Colorblind-safe palettes

## Testing Considerations

1. **CSS Variable Support**
   - Test in all major browsers
   - Fallback values for older browsers

2. **Performance Testing**
   - Measure paint times with many sections
   - Test with complex gradients

3. **User Testing**
   - Ensure settings are discoverable
   - Test with non-technical users
   - Validate preset selections

## Security Considerations

1. **Sanitization**
   - Validate all color inputs
   - Escape output in CSS
   - Nonce verification on AJAX

2. **Permissions**
   - Only administrators can change global settings
   - Consider adding custom capability

3. **Data Validation**
   - Validate JSON structure
   - Limit setting values
   - Prevent CSS injection

---

This implementation follows industry best practices from Elementor and Breakdance while maintaining simplicity and performance. The CSS variable approach ensures instant updates and consistent styling across all sections.
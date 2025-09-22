# Business Logic Documentation

## Core Business Rules

This document defines the business logic that must be preserved in the modern rebuild. These rules govern how the plugin behaves and ensures consistency.

## Section Management Rules

### Section Creation
1. **Minimum Requirements**:
   - Every section MUST have a type
   - Every section MUST have a unique index
   - Hero sections MUST have a heading (required field)
   - Other sections CAN have empty headings

2. **Default Values**:
   ```javascript
   {
     type: 'hero',
     content: {
       heading: 'Your Headline Here',
       content: '<p>Your compelling message goes here</p>',
       theme_variant: 'dark',  // Hero defaults to dark
       layout_variant: 'content-left',
       media_type: 'none',
       global_blocks: []
     }
   }
   ```

3. **Section Limits**:
   - No maximum section limit
   - Minimum 0 sections (empty page allowed)
   - Recommended max: 20 sections (performance)

### Section Ordering
1. **Rules**:
   - Sections render in array order (index 0 = top)
   - Drag-drop reordering updates array indices
   - New sections append to end (bottom)
   - No automatic reordering

2. **Constraints**:
   - Cannot have duplicate indices
   - Cannot have gaps in indices
   - Must reindex after deletion

## Field Validation Rules

### Text Fields
1. **Heading Fields**:
   - Max length: 200 characters
   - HTML not allowed (stripped)
   - Line breaks converted to spaces

2. **Eyebrow Headings**:
   - Max length: 100 characters
   - Optional field
   - HTML stripped

3. **WYSIWYG Content**:
   - Allowed tags: p, br, strong, em, u, a, ul, ol, li, h3, h4
   - Scripts stripped for security
   - Max length: 10,000 characters

### Media Fields
1. **Images**:
   - Accept: .jpg, .jpeg, .png, .gif, .webp, .svg
   - Max size: 5MB (WordPress default)
   - Must be valid WordPress attachment OR external URL
   - Fallback to placeholder if invalid

2. **Videos**:
   - Accept: YouTube, Vimeo URLs only
   - Must be valid embed URL
   - No local video files (performance)

### URL Fields
1. **Validation**:
   - Must start with http://, https://, /, or #
   - Relative URLs allowed for internal links
   - JavaScript: protocol blocked (XSS prevention)
   - Max length: 2000 characters

2. **Target Options**:
   - _self (default) - same window
   - _blank - new window (adds rel="noopener noreferrer")

## Button Rules

### Button Types
1. **Primary Button**:
   - One primary per button group recommended
   - Blue background, white text
   - Used for main CTA

2. **Secondary Button**:
   - Unlimited secondary buttons
   - Transparent background, border
   - Used for alternative actions

### Button Constraints
- Min buttons: 0
- Max buttons: 5 per section (UX recommendation)
- Button text required if button exists
- Button URL required if button exists

## Repeater Field Rules

### Features Cards
- Min items: 0
- Max items: 12 (grid layout limit)
- Each card independent
- Empty cards not rendered

### FAQ Items
- Min items: 0
- Max items: 50
- Question required
- Answer required
- Both question and answer support rich text

### Checklist Items
- Min items: 0  
- Max items: 20
- Heading required
- Content optional

### Stats Items
- Min items: 1 (don't show empty stats)
- Max items: 8 (layout constraint)
- Number required
- Label required
- Description optional

### Testimonials
- Min items: 0
- Max items: 20
- Content required (the quote)
- Author name required
- Rating: 1-5 stars (default 5)

## Theme Variant Rules

### Light Theme
- White background
- Dark text
- Default for: Features, Checklist, FAQ, Stats, Testimonials

### Dark Theme  
- Dark background
- Light text
- Default for: Hero, HeroForm

### Inheritance
- Child elements inherit parent theme
- Buttons adapt to theme automatically
- Media overlays respect theme

## Layout Variant Rules

### Content-Left
- Content on left, media on right
- Default for: Hero, Features
- Mobile: Stacks with content first

### Content-Right
- Content on right, media on left
- Available for: Hero, Features
- Mobile: Stacks with content first

### Center
- Content centered, media below
- Default for: FAQ, Stats, Testimonials
- Available for all sections

## Save/Load Rules

### Save Triggers
1. **Manual Save**:
   - User clicks Save button
   - Shows loading indicator
   - Confirms with success message
   - Clears dirty flag

2. **Auto-Save** (planned):
   - Every 30 seconds if dirty
   - On page unload (with warning)
   - Draft status

### Data Storage
1. **Location**:
   - Post meta key: `_aisb_sections`
   - Format: JSON string
   - Escaped for database storage

2. **Compatibility**:
   - Backward compatible with v1 data
   - Forward compatible structure
   - Version migration on load

## AI Generation Rules

### Content Generation
1. **Context Required**:
   - Business type/industry
   - Target audience
   - Key message/goal

2. **Limits**:
   - Max 3 sections per request
   - Rate limit: 10 requests per minute
   - Requires valid API key

3. **Content Rules**:
   - Must be appropriate (filtered)
   - Must match section type
   - Must include all required fields

### Document Processing
1. **Supported Formats**:
   - .docx, .doc
   - .txt
   - .pdf (text only)

2. **Processing Rules**:
   - Max file size: 2MB
   - Extract headings for structure
   - Paragraphs become content
   - Lists become checkpoints/features

## Global Settings Rules

### Color Settings
1. **Inheritance**:
   - Global colors cascade to all sections
   - Section-specific overrides possible
   - CSS variables for live updates

2. **Validation**:
   - Must be valid hex colors
   - Contrast ratio checked (WCAG AA)
   - Fallback to defaults if invalid

## User Permission Rules

### Editor Access
- Requires: `edit_pages` capability
- Admin and Editor roles by default
- Custom roles need explicit capability

### Save Permissions
- Same as editor access
- Nonce verification required
- Post-specific permissions checked

## Frontend Rendering Rules

### Section Display
1. **Conditions**:
   - Only saved sections render
   - Empty sections not displayed
   - Hidden sections skipped (future feature)

2. **Order**:
   - Exact same order as editor
   - No reordering on frontend
   - No conditional display (yet)

### Responsive Behavior
1. **Breakpoints**:
   - Mobile: < 640px
   - Tablet: 640px - 1024px
   - Desktop: > 1024px

2. **Layout Changes**:
   - Multi-column → Single column on mobile
   - Side-by-side → Stacked on mobile
   - Font sizes scale down

## Error Handling Rules

### User Errors
1. **Validation Failures**:
   - Show inline error messages
   - Highlight problem fields
   - Prevent save until fixed

2. **Required Fields**:
   - Mark with asterisk (*)
   - Show "Required" message
   - Focus on first error

### System Errors
1. **Network Failures**:
   - Retry 3 times with exponential backoff
   - Show user-friendly error message
   - Preserve unsaved changes

2. **Server Errors**:
   - Log to console for debugging
   - Show generic error to user
   - Suggest refresh if critical

## Migration Rules

### Version Updates
1. **Data Migration**:
   - Detect old version on load
   - Transform to new structure
   - Preserve all content
   - Log migration for debugging

2. **Breaking Changes**:
   - Never remove fields (deprecate)
   - Always provide fallbacks
   - Test with real user data

---

These business rules ensure consistent behavior between the current version and the modern rebuild.
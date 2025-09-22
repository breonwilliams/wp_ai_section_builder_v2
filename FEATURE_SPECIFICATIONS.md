# Feature Specifications - AI Section Builder v2

## Complete Feature List

This document specifies every feature that must be preserved or improved in the modern rebuild.

## Section Types

### 1. Hero Section

**Purpose**: Eye-catching opener with headline and call-to-action

**Fields**:
```javascript
{
  eyebrow_heading: string,     // Small text above headline
  heading: string,              // Main headline (required)
  content: html,                // WYSIWYG body content
  outro_content: html,          // Additional content after buttons
  media_type: 'none'|'image'|'video',
  featured_image: url,          // WordPress media ID or URL
  video_url: url,               // YouTube/Vimeo URL
  theme_variant: 'light'|'dark',
  layout_variant: 'content-left'|'content-right'|'center',
  global_blocks: [              // Button array
    {
      type: 'button',
      style: 'primary'|'secondary',
      text: string,
      url: string,
      target: '_self'|'_blank'
    }
  ]
}
```

**Features**:
- Live preview updates
- Media on left/right/center
- Multiple buttons support
- Light/dark theme variants
- WYSIWYG editor for content

### 2. Hero with Form Section

**Purpose**: Hero variant with embedded form area

**Fields**: Same as Hero, plus:
```javascript
{
  form_type: 'placeholder'|'shortcode',
  form_shortcode: string,      // e.g., [contact-form-7 id="123"]
  form_title: string,          // Form area heading
  placeholder_fields: [        // If placeholder type
    { label: string, type: 'text'|'email'|'select' }
  ]
}
```

**Features**:
- Supports any WordPress form plugin via shortcode
- Placeholder form for design preview
- Form on right side of content

### 3. Features Section

**Purpose**: Grid of feature cards with icons

**Fields**:
```javascript
{
  eyebrow_heading: string,
  heading: string,
  content: html,              // Intro content
  outro_content: html,
  theme_variant: 'light'|'dark',
  layout_variant: 'content-left'|'content-right'|'center',
  card_alignment: 'left'|'center',
  cards: [                    // Feature cards array
    {
      image: url,             // Icon/image
      heading: string,
      content: text,
      link: url,
      link_text: string,
      link_target: '_self'|'_blank'
    }
  ],
  global_blocks: [],          // Buttons
  media_type: string,
  featured_image: url,
  video_url: url
}
```

**Features**:
- Unlimited feature cards
- Optional images for each card
- Individual card links
- Grid layout (3 columns desktop, responsive)
- Reorderable cards

### 4. Checklist Section

**Purpose**: List of benefits or features with checkmarks

**Fields**:
```javascript
{
  eyebrow_heading: string,
  heading: string,
  content: html,
  outro_content: html,
  theme_variant: 'light'|'dark',
  layout_variant: 'content-left'|'content-right'|'center',
  items: [                    // Checklist items
    {
      heading: string,
      content: text
    }
  ],
  global_blocks: [],          // Buttons
  media_type: string,
  featured_image: url
}
```

**Features**:
- Check icon for each item
- Two-column layout option
- Reorderable items
- Optional media display

### 5. FAQ Section

**Purpose**: Expandable accordion for frequently asked questions

**Fields**:
```javascript
{
  eyebrow_heading: string,
  heading: string,
  content: html,              // Intro text
  outro_content: html,
  theme_variant: 'light'|'dark',
  layout_variant: 'center'|'content-left',
  questions: [                // FAQ items (also supports 'faq_items' key)
    {
      question: string,
      answer: html            // Rich text answer
    }
  ],
  global_blocks: [],
  media_type: string,
  featured_image: url
}
```

**Features**:
- Accordion expand/collapse
- WYSIWYG for answers
- Smooth animations
- Reorderable questions
- Keyboard accessible

### 6. Stats Section

**Purpose**: Display key metrics and numbers

**Fields**:
```javascript
{
  eyebrow_heading: string,
  heading: string,
  content: html,
  outro_content: html,
  theme_variant: 'light'|'dark',
  layout_variant: 'center',
  stats: [                    // Statistics items
    {
      number: string,         // e.g., "99%", "$1M", "500+"
      label: string,          // e.g., "Customer Satisfaction"
      description: string     // Optional detail text
    }
  ],
  global_blocks: [],
  media_type: string,
  featured_image: url
}
```

**Features**:
- Large number display
- Grid layout (4 columns desktop)
- Optional descriptions
- Animated number counting (frontend)

### 7. Testimonials Section

**Purpose**: Customer reviews and testimonials

**Fields**:
```javascript
{
  eyebrow_heading: string,
  heading: string,
  content: html,
  outro_content: html,
  theme_variant: 'light'|'dark',
  layout_variant: 'center',
  testimonials: [
    {
      rating: 1-5,            // Star rating
      content: text,          // Quote text
      author_name: string,
      author_title: string,   // e.g., "CEO, Company"
      author_image: url       // Avatar
    }
  ],
  global_blocks: [],
  media_type: string,
  featured_image: url
}
```

**Features**:
- 5-star rating display
- Author avatars
- Grid layout (3 columns desktop)
- Quote formatting

## Editor Features

### Core Functionality

1. **Visual Canvas**
   - Live preview of all sections
   - Accurate representation of frontend
   - Theme variant switching

2. **Section Management**
   - Add sections from library
   - Delete sections with confirmation
   - Reorder via drag-and-drop
   - Duplicate sections (planned)

3. **Form Editing**
   - Field validation
   - Required field indicators
   - Placeholder text
   - Help text for fields

4. **Media Management**
   - WordPress Media Library integration
   - Image upload and selection
   - Video URL validation
   - Preview in editor

5. **Save System**
   - Manual save button
   - Dirty state indicator
   - Success/error notifications
   - Save to post meta

### UI Components

1. **Left Sidebar**
   - Section library (add new)
   - Section edit forms
   - Settings panel
   - Tabbed interface

2. **Canvas (Center)**
   - Section previews
   - Empty state message
   - Responsive width

3. **Right Sidebar**
   - Page structure list
   - Drag handles for reordering
   - Quick actions (edit/delete)
   - Section type indicators

4. **Toolbar**
   - Save button
   - Back to editor link
   - Clear all sections
   - Upload document (AI feature)
   - Toggle sidebars

### Advanced Features

1. **AI Integration**
   - Generate content from prompts
   - Document upload and parsing
   - Section-specific AI generation
   - Context-aware suggestions

2. **Global Settings**
   - Primary color
   - Light/dark theme colors
   - Text colors
   - Background colors
   - Border colors
   - Applied to all sections

3. **Repeater Fields**
   - Add/remove items dynamically
   - Reorder items
   - Minimum/maximum limits
   - Default values

4. **WYSIWYG Editor**
   - TinyMCE integration
   - Basic formatting (bold, italic, links)
   - Paragraph/heading support
   - Clean HTML output

## Frontend Features

1. **Responsive Design**
   - Mobile-first approach
   - Breakpoints: 640px, 768px, 1024px
   - Touch-friendly interactions

2. **Animations**
   - FAQ accordion smooth expand
   - Scroll animations (optional)
   - Hover effects on buttons

3. **Accessibility**
   - Keyboard navigation
   - ARIA labels
   - Screen reader support
   - Focus indicators

4. **Performance**
   - Lazy loading images
   - Optimized CSS delivery
   - Minimal JavaScript

## WordPress Integration

1. **Post Types**
   - Works with pages
   - Works with posts
   - Custom post type support

2. **User Capabilities**
   - Edit pages permission required
   - Admin menu visible to editors+
   - Save restricted to capable users

3. **Shortcode Support**
   - Form shortcodes in Hero-Form
   - Other shortcodes in content areas

4. **Theme Compatibility**
   - Works with any theme
   - Isolated styling
   - No conflicts with theme CSS

## Data Management

1. **Storage Format**
   - JSON in post meta
   - Backward compatible structure
   - Version migration support

2. **Import/Export** (Planned)
   - Export sections as JSON
   - Import from JSON
   - Template library

3. **Revisions** (Planned)
   - Track changes
   - Restore previous versions
   - Diff viewer

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Targets

- Initial load: < 2 seconds
- Section add: < 200ms
- Preview update: < 100ms
- Save operation: < 1 second
- Smooth 60fps interactions

---

All these features must be preserved or enhanced in the modern rebuild.
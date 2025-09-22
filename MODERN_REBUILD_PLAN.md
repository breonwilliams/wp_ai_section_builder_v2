# Modern Rebuild Technical Implementation Plan

## Technology Stack

### Core Technologies
- **React 18** - UI library
- **Zustand** - State management (simpler than Redux)
- **React Query** - Server state management
- **Axios** - HTTP client
- **WordPress REST API** - Backend communication

### Build Tools
- **@wordpress/scripts** - WordPress's official build setup
- **Webpack 5** - Module bundler
- **Babel** - JavaScript transpiler
- **PostCSS** - CSS processing
- **Tailwind CSS** - Utility-first CSS framework

### Development Tools
- **TypeScript** (optional, progressive adoption)
- **ESLint** - Code linting
- **Prettier** - Code formatting
- **Jest** - Testing framework
- **React Testing Library** - Component testing

## Project Structure

```
ai-section-builder-modern/
├── src/
│   ├── components/
│   │   ├── Editor/
│   │   │   ├── Editor.jsx              // Main editor component
│   │   │   ├── Canvas.jsx              // Preview area
│   │   │   ├── Toolbar.jsx             // Top toolbar
│   │   │   ├── LeftSidebar/
│   │   │   │   ├── index.jsx
│   │   │   │   ├── SectionLibrary.jsx  // Add section buttons
│   │   │   │   ├── SectionForm.jsx     // Edit forms
│   │   │   │   └── SettingsPanel.jsx   // Global settings
│   │   │   └── RightSidebar/
│   │   │       ├── index.jsx
│   │   │       └── PageStructure.jsx   // Section list
│   │   ├── Sections/
│   │   │   ├── Base/
│   │   │   │   ├── SectionWrapper.jsx  // Common wrapper
│   │   │   │   ├── SectionHeader.jsx   // Eyebrow + heading
│   │   │   │   └── SectionButtons.jsx  // Button renderer
│   │   │   ├── Hero/
│   │   │   │   ├── index.jsx           // Hero component
│   │   │   │   ├── HeroForm.jsx        // Edit form
│   │   │   │   ├── HeroPreview.jsx     // Preview
│   │   │   │   └── hero.module.css     // Styles
│   │   │   ├── Features/
│   │   │   ├── FAQ/
│   │   │   ├── Stats/
│   │   │   ├── Testimonials/
│   │   │   ├── Checklist/
│   │   │   └── HeroForm/
│   │   ├── Common/
│   │   │   ├── RichTextEditor/
│   │   │   │   ├── index.jsx
│   │   │   │   └── TinyMCEWrapper.jsx
│   │   │   ├── MediaPicker/
│   │   │   │   ├── index.jsx
│   │   │   │   └── WordPressMedia.jsx
│   │   │   ├── ColorPicker.jsx
│   │   │   ├── RepeaterField/
│   │   │   │   ├── index.jsx
│   │   │   │   └── RepeaterItem.jsx
│   │   │   ├── Button.jsx
│   │   │   ├── Input.jsx
│   │   │   ├── Select.jsx
│   │   │   └── Modal.jsx
│   │   └── Providers/
│   │       ├── EditorProvider.jsx      // Context wrapper
│   │       └── ToastProvider.jsx       // Notifications
│   ├── stores/
│   │   ├── useEditorStore.js           // Main editor state
│   │   ├── useSectionsStore.js         // Sections data
│   │   └── useSettingsStore.js         // Global settings
│   ├── hooks/
│   │   ├── useAutoSave.js
│   │   ├── useKeyboardShortcuts.js
│   │   ├── useDragDrop.js
│   │   └── useMediaLibrary.js
│   ├── api/
│   │   ├── client.js                   // Axios instance
│   │   ├── sections.js                 // Section CRUD
│   │   ├── ai.js                       // AI generation
│   │   └── media.js                    // Media operations
│   ├── utils/
│   │   ├── sanitization.js             // XSS prevention
│   │   ├── validation.js               // Form validation
│   │   ├── sectionHelpers.js           // Section utilities
│   │   └── constants.js                // App constants
│   ├── styles/
│   │   ├── globals.css                 // Global styles
│   │   ├── variables.css               // CSS variables
│   │   └── tailwind.css                // Tailwind imports
│   └── index.js                        // Entry point
├── build/                               // Compiled files
├── includes/                           // PHP files
│   ├── API/
│   │   ├── SectionsController.php      // REST endpoints
│   │   ├── AIController.php
│   │   └── SettingsController.php
│   ├── Core/
│   │   ├── Plugin.php
│   │   ├── Activator.php
│   │   └── Assets.php
│   └── Render/
│       └── SectionRenderer.php         // Frontend rendering
├── templates/
│   └── editor.php                      // Editor page template
├── webpack.config.js
├── package.json
├── composer.json                       // PHP dependencies
└── ai-section-builder-modern.php       // Plugin entry
```

## Component Architecture

### State Management Pattern

```javascript
// stores/useEditorStore.js
import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

const useEditorStore = create(devtools((set, get) => ({
  // State
  sections: [],
  currentSectionIndex: null,
  isDirty: false,
  isLoading: false,
  
  // Actions
  addSection: (type) => set((state) => ({
    sections: [...state.sections, createNewSection(type)],
    isDirty: true
  })),
  
  updateSection: (index, data) => set((state) => ({
    sections: state.sections.map((s, i) => 
      i === index ? { ...s, ...data } : s
    ),
    isDirty: true
  })),
  
  deleteSection: (index) => set((state) => ({
    sections: state.sections.filter((_, i) => i !== index),
    isDirty: true
  })),
  
  reorderSections: (fromIndex, toIndex) => set((state) => {
    const newSections = [...state.sections];
    const [moved] = newSections.splice(fromIndex, 1);
    newSections.splice(toIndex, 0, moved);
    return { sections: newSections, isDirty: true };
  })
})));
```

### Component Pattern

```javascript
// components/Sections/Hero/index.jsx
import { memo } from 'react';
import { useEditorStore } from '@/stores/useEditorStore';
import SectionWrapper from '../Base/SectionWrapper';
import HeroPreview from './HeroPreview';
import styles from './hero.module.css';

const HeroSection = memo(({ section, index }) => {
  const updateSection = useEditorStore(state => state.updateSection);
  
  const handleUpdate = (field, value) => {
    updateSection(index, { 
      content: { 
        ...section.content, 
        [field]: value 
      } 
    });
  };
  
  return (
    <SectionWrapper 
      className={styles.hero}
      theme={section.content.theme_variant}
      layout={section.content.layout_variant}
    >
      <HeroPreview 
        content={section.content}
        onUpdate={handleUpdate}
      />
    </SectionWrapper>
  );
});

export default HeroSection;
```

### API Integration Pattern

```javascript
// api/sections.js
import client from './client';

export const sectionsAPI = {
  async load(postId) {
    const { data } = await client.get(`/sections/${postId}`);
    return data.sections;
  },
  
  async save(postId, sections) {
    const { data } = await client.post(`/sections/${postId}`, {
      sections
    });
    return data;
  },
  
  async generateWithAI(type, context) {
    const { data } = await client.post('/ai/generate', {
      type,
      context
    });
    return data.content;
  }
};
```

## REST API Endpoints

```php
// WordPress REST API routes
register_rest_route('aisb/v1', '/sections/(?P<id>\d+)', [
  'methods' => 'GET',
  'callback' => 'get_sections',
  'permission_callback' => 'can_edit_post'
]);

register_rest_route('aisb/v1', '/sections/(?P<id>\d+)', [
  'methods' => 'POST',
  'callback' => 'save_sections',
  'permission_callback' => 'can_edit_post'
]);

register_rest_route('aisb/v1', '/ai/generate', [
  'methods' => 'POST',
  'callback' => 'generate_ai_content',
  'permission_callback' => 'can_edit_posts'
]);
```

## Key Improvements

### 1. Performance Optimizations
```javascript
// Virtualized section list for large pages
import { FixedSizeList } from 'react-window';

// Memoized renders
const SectionPreview = memo(({ section }) => {
  // Only re-renders when section changes
});

// Debounced saves
const debouncedSave = useMemo(
  () => debounce(saveSections, 1000),
  []
);
```

### 2. Security Enhancements
```javascript
// utils/sanitization.js
import DOMPurify from 'dompurify';

export const sanitizeHTML = (html) => {
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: ['href', 'target', 'rel']
  });
};

// React escapes by default, but for dynamic HTML:
<div dangerouslySetInnerHTML={{ 
  __html: sanitizeHTML(content) 
}} />
```

### 3. Better UX
```javascript
// Optimistic updates
const handleSave = async () => {
  // Update UI immediately
  setStatus('saved');
  
  try {
    await api.save(sections);
  } catch (error) {
    // Rollback on error
    setStatus('error');
    rollbackSections();
  }
};

// Undo/Redo support
const history = useHistory();
const undo = () => history.undo();
const redo = () => history.redo();
```

## Migration Strategy

### Phase 1: Core Setup (Week 1)
- Setup build environment
- Create basic React app structure
- Implement WordPress REST API
- Setup authentication

### Phase 2: Editor Shell (Week 2)
- Build layout components
- Implement state management
- Add routing
- Create notification system

### Phase 3: Section Components (Weeks 3-4)
- Port Hero section first
- Build reusable components
- Implement all 7 section types
- Add form validation

### Phase 4: Features (Week 5)
- Media library integration
- AI content generation
- Drag-and-drop ordering
- Keyboard shortcuts

### Phase 5: Polish (Week 6)
- Performance optimization
- Accessibility improvements
- Error handling
- Loading states

### Phase 6: Testing & Migration (Week 7)
- Unit tests
- Integration tests
- Migration scripts
- Documentation

## Development Workflow

```bash
# Development
npm start           # Start dev server with HMR
npm run lint        # Run ESLint
npm test           # Run tests

# Production
npm run build      # Create production bundle
npm run analyze    # Analyze bundle size

# WordPress
wp plugin activate ai-section-builder-modern
```

## Performance Targets

- **Bundle size**: < 250KB gzipped
- **Time to interactive**: < 2s
- **Section render**: < 16ms (60fps)
- **API response**: < 200ms

## Browser Support

- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- Progressive enhancement for older browsers
- Mobile responsive

## Success Metrics

1. ✅ 50% faster than jQuery version
2. ✅ 70% smaller codebase
3. ✅ Zero security vulnerabilities  
4. ✅ 100% feature parity with v2
5. ✅ < 1% error rate in production
6. ✅ 90+ Lighthouse score

---

This plan provides a clear roadmap for rebuilding the plugin with modern best practices while maintaining all existing functionality.
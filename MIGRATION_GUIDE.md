# AI Section Builder - Migration to Modern Architecture Guide

## For New Claude Code Instance

This guide provides step-by-step instructions for rebuilding the AI Section Builder plugin using modern development practices.

## Prerequisites

Before starting, ensure you have:
- Node.js 18+ and npm
- WordPress development environment (Local, XAMPP, or similar)
- Basic knowledge of React and WordPress development
- This repository cloned as reference: `ai-section-builder-v2-reference`

## Project Setup Instructions

### Step 1: Initialize New Project

```bash
# Create new project folder
mkdir ai-section-builder-modern
cd ai-section-builder-modern

# Initialize npm project
npm init -y

# Create WordPress plugin structure
mkdir -p src/{components,stores,api,utils,styles}
mkdir -p includes/{admin,core,rest}
mkdir -p assets/{css,js,images}
mkdir -p templates
```

### Step 2: Install Dependencies

```bash
# React and build tools
npm install --save react react-dom @wordpress/element @wordpress/components
npm install --save zustand axios

# Development dependencies
npm install --save-dev @wordpress/scripts webpack webpack-cli
npm install --save-dev @babel/core @babel/preset-react
npm install --save-dev css-loader style-loader postcss tailwindcss
npm install --save-dev eslint prettier
```

### Step 3: Configure Build System

Create `webpack.config.js`:
```javascript
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        editor: './src/editor.js',
        frontend: './src/frontend.js'
    },
    output: {
        path: __dirname + '/build',
        filename: '[name].js'
    }
};
```

### Step 4: Setup Plugin Base Files

Create main plugin file `ai-section-builder-modern.php`:
```php
<?php
/**
 * Plugin Name: AI Section Builder Modern
 * Description: Modern rebuild of AI Section Builder with React
 * Version: 3.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Use existing PHP structure from v2 as reference
// Copy and modernize from ai-section-builder-v2-reference
```

## Development Order

### Phase 1: Core Infrastructure (Week 1)
1. Setup WordPress REST API endpoints
2. Create plugin activation/deactivation hooks
3. Setup database tables (reuse existing schema)
4. Create admin menu structure

### Phase 2: React Editor Foundation (Week 2)
1. Build main Editor component
2. Create layout: Sidebar + Canvas + Structure Panel
3. Implement state management with Zustand
4. Setup router for different views

### Phase 3: Section Components (Week 3-4)
1. Start with Hero section as template
2. Build reusable components:
   - RichTextEditor (WYSIWYG)
   - MediaPicker
   - ColorPicker
   - ButtonEditor
3. Implement each section type:
   - Hero
   - HeroForm
   - Features
   - Checklist
   - FAQ
   - Stats
   - Testimonials

### Phase 4: Data Management (Week 5)
1. Implement save/load via REST API
2. Add autosave functionality
3. Create revision system
4. Add import/export features

### Phase 5: AI Integration (Week 6)
1. Port existing AI functionality
2. Enhance with streaming responses
3. Add AI template suggestions
4. Implement smart content generation

### Phase 6: Polish & Optimization (Week 7-8)
1. Add keyboard shortcuts
2. Implement undo/redo
3. Add drag-and-drop reordering
4. Optimize bundle size
5. Add loading states and error handling

## Key Improvements to Implement

### 1. Security Fixes
- **XSS Prevention**: React escapes by default, but sanitize all user inputs
- **Nonce Verification**: Use WordPress REST API built-in nonce handling
- **Capability Checks**: Verify user permissions on all endpoints

### 2. Performance Optimizations
- **Code Splitting**: Load only needed section components
- **Virtual Scrolling**: For long pages with many sections
- **Debounced Saves**: Prevent excessive API calls
- **Optimistic Updates**: Update UI before server confirms

### 3. Better UX
- **Real-time Collaboration Ready**: Structure for future WebSocket support
- **Responsive Design**: Mobile-friendly editor
- **Accessibility**: Full keyboard navigation and screen reader support
- **Inline Editing**: Edit text directly in preview

### 4. Developer Experience
- **TypeScript Ready**: Add types progressively
- **Component Library**: Storybook for component development
- **Testing**: Jest + React Testing Library
- **Documentation**: JSDoc comments throughout

## Migration Checklist

- [ ] Setup new project structure
- [ ] Configure build tools
- [ ] Create REST API endpoints
- [ ] Build Editor shell
- [ ] Implement state management
- [ ] Create Hero section component
- [ ] Port all section types
- [ ] Implement save/load
- [ ] Add AI features
- [ ] Fix all known issues from v2
- [ ] Add new improvements
- [ ] Test thoroughly
- [ ] Create migration tool for existing data
- [ ] Write user documentation
- [ ] Prepare for deployment

## Reference Files to Study

From `ai-section-builder-v2-reference/`:
1. `assets/js/editor/editor.js` - Contains all business logic
2. `includes/core/class-ajax-handler.php` - Save/load mechanisms
3. `includes/sections/` - PHP section renderers
4. `templates/editor-page.php` - Editor HTML structure
5. `assets/css/` - Existing styles to port

## Critical Functions to Port

These functions from v2 must be reimplemented:
1. `saveSections()` - lines 4985-5057
2. `renderSection()` - lines 4552-4586
3. `generateHeroForm()` - lines 1194-1266
4. Form field generators (lines 1194-2053)
5. AI integration (search for 'ai_generate' in editor.js)

## Modern Equivalents

| Current (jQuery) | Modern (React) |
|-----------------|----------------|
| `$('#element').html(content)` | `setState({ content })` |
| `$(document).on('click', ...)` | `onClick={handleClick}` |
| Global `editorState` | Zustand store |
| AJAX calls | Axios with REST API |
| HTML string templates | JSX components |
| Manual DOM updates | React re-renders |

## Success Criteria

The modern version should:
1. ✅ Load 50% faster than v2
2. ✅ No jQuery dependency
3. ✅ Modular, maintainable code
4. ✅ Pass all security audits
5. ✅ Support all v2 features
6. ✅ Add improved UX features
7. ✅ Work with existing data
8. ✅ Be extensible for future features

## Getting Help

When you encounter issues:
1. Check the original implementation in v2 reference
2. Refer to BUSINESS_LOGIC.md for rules
3. See KNOWN_ISSUES_TO_FIX.md for bugs to avoid
4. Follow patterns in COMPONENT_MAPPING.md

## Start Command

Once setup is complete:
```bash
npm run start # Development with hot reload
npm run build # Production build
```

---

**Important**: Start with Phase 1 and complete each phase before moving to the next. The v2 reference contains all the working logic - your job is to restructure it properly, not reinvent it.
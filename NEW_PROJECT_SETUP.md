# New Project Setup Instructions

## For New Claude Code Instance

Follow these steps exactly to set up the modern rebuild project.

## Step 1: Create Project Structure

```bash
# Create new project folder (separate from v2)
cd /Users/breonwilliams/Local Sites/ai-section-builder/app/public/wp-content/plugins/
mkdir ai-section-builder-modern
cd ai-section-builder-modern

# Copy v2 as reference (DO NOT MODIFY)
cp -r ../ai_section_builder_v2 ../ai_section_builder_v2_reference
```

## Step 2: Initialize Project

```bash
# Initialize npm project
npm init -y

# Update package.json
npm pkg set name="ai-section-builder-modern"
npm pkg set version="3.0.0"
npm pkg set description="Modern React-based section builder for WordPress"
```

## Step 3: Install Dependencies

```bash
# Core dependencies
npm install react react-dom
npm install zustand immer
npm install axios
npm install dompurify
npm install classnames

# WordPress dependencies
npm install @wordpress/element @wordpress/components @wordpress/i18n
npm install @wordpress/api-fetch @wordpress/data

# UI Components
npm install @headlessui/react @heroicons/react
npm install react-hot-toast
npm install @dnd-kit/sortable @dnd-kit/core

# Development dependencies
npm install -D @wordpress/scripts
npm install -D tailwindcss postcss autoprefixer
npm install -D eslint prettier eslint-config-prettier
npm install -D @types/wordpress__components @types/react @types/react-dom
```

## Step 4: Create Configuration Files

### webpack.config.js
```javascript
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    editor: path.resolve(process.cwd(), 'src', 'index.js'),
    frontend: path.resolve(process.cwd(), 'src', 'frontend.js'),
  },
  output: {
    path: path.resolve(process.cwd(), 'build'),
    filename: '[name].js',
  },
};
```

### tailwind.config.js
```javascript
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: 'var(--aisb-primary)',
        secondary: 'var(--aisb-secondary)',
      }
    },
  },
  plugins: [],
}
```

### .eslintrc.json
```json
{
  "extends": [
    "plugin:@wordpress/eslint-plugin/recommended",
    "prettier"
  ],
  "rules": {
    "react/prop-types": "off"
  }
}
```

### .prettierrc
```json
{
  "singleQuote": true,
  "trailingComma": "es5",
  "tabWidth": 2,
  "semi": true,
  "printWidth": 80
}
```

## Step 5: Create Plugin Base File

### ai-section-builder-modern.php
```php
<?php
/**
 * Plugin Name: AI Section Builder Modern
 * Plugin URI: https://your-site.com/
 * Description: Modern React-based section builder with AI capabilities
 * Version: 3.0.0
 * Author: Your Name
 * Text Domain: ai-section-builder-modern
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AISB_MODERN_VERSION', '3.0.0');
define('AISB_MODERN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AISB_MODERN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
require_once AISB_MODERN_PLUGIN_DIR . 'includes/Core/Plugin.php';

// Initialize plugin
function aisb_modern_init() {
    $plugin = new AISB\Modern\Core\Plugin();
    $plugin->init();
}
add_action('plugins_loaded', 'aisb_modern_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    require_once AISB_MODERN_PLUGIN_DIR . 'includes/Core/Activator.php';
    AISB\Modern\Core\Activator::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once AISB_MODERN_PLUGIN_DIR . 'includes/Core/Deactivator.php';
    AISB\Modern\Core\Deactivator::deactivate();
});
```

## Step 6: Create Folder Structure

```bash
# Create source directories
mkdir -p src/{components,stores,hooks,api,utils,styles}
mkdir -p src/components/{Editor,Sections,Common,Providers}
mkdir -p src/components/Sections/{Hero,Features,FAQ,Stats,Testimonials,Checklist,HeroForm}
mkdir -p src/components/Common/{Button,Input,Modal,MediaPicker,RichTextEditor,RepeaterField}

# Create PHP directories
mkdir -p includes/{API,Core,Render,Admin}

# Create asset directories
mkdir -p assets/{images,fonts}

# Create template directory
mkdir -p templates
```

## Step 7: Development Scripts

Add to package.json:
```json
{
  "scripts": {
    "start": "wp-scripts start",
    "build": "wp-scripts build",
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style",
    "test": "wp-scripts test-unit-js",
    "format": "wp-scripts format"
  }
}
```

## Step 8: Initialize Git

```bash
# Initialize git repository
git init

# Create .gitignore
cat > .gitignore << 'EOF'
# Dependencies
node_modules/
vendor/

# Build files
build/
dist/

# Development files
.DS_Store
*.log
.env
.env.local

# IDE files
.vscode/
.idea/
*.swp
*.swo

# WordPress files
*.sql
wp-config.php
EOF

# Initial commit
git add .
git commit -m "Initial setup for AI Section Builder Modern"
```

## Step 9: Reference Documentation

**IMPORTANT**: Read these files from the v2_reference folder:
1. `MIGRATION_GUIDE.md` - Overall migration strategy
2. `CURRENT_ARCHITECTURE.md` - Understand existing structure
3. `FEATURE_SPECIFICATIONS.md` - All features to implement
4. `MODERN_REBUILD_PLAN.md` - Technical implementation details
5. `KNOWN_ISSUES_TO_FIX.md` - Issues to avoid/fix
6. `BUSINESS_LOGIC.md` - Business rules to preserve
7. `COMPONENT_MAPPING.md` - Component structure

## Step 10: Start Development

```bash
# Start development server
npm start

# In another terminal, watch for changes
npm run start:hot
```

## Development Order

### Week 1: Foundation
1. Setup WordPress admin page
2. Create REST API endpoints
3. Basic React app shell
4. State management setup

### Week 2: Core Components
1. Editor layout
2. Section canvas
3. Sidebar panels
4. Basic section rendering

### Week 3-4: Section Components
1. Start with Hero section
2. Build form generators
3. Add preview components
4. Implement all 7 sections

### Week 5: Features
1. Save/load functionality
2. Media library integration
3. Drag-and-drop ordering
4. AI integration

### Week 6: Polish
1. Error handling
2. Loading states
3. Animations
4. Performance optimization

### Week 7: Testing
1. Unit tests
2. Integration tests
3. User acceptance testing
4. Bug fixes

## Important Notes

### DO:
- ✅ Start with Hero section as template
- ✅ Use v2_reference for business logic
- ✅ Test each component in isolation
- ✅ Commit frequently
- ✅ Follow React best practices

### DON'T:
- ❌ Copy jQuery code directly
- ❌ Use global variables
- ❌ Skip security sanitization
- ❌ Ignore accessibility
- ❌ Forget mobile responsiveness

## Getting Help

When stuck:
1. Check the v2_reference implementation
2. Refer to documentation files
3. Look for similar patterns in the codebase
4. Test in isolation first
5. Use React DevTools for debugging

## Success Checklist

Before considering complete:
- [ ] All 7 section types working
- [ ] Save/load functioning
- [ ] AI integration complete
- [ ] No console errors
- [ ] Passes all tests
- [ ] Mobile responsive
- [ ] Keyboard accessible
- [ ] XSS vulnerabilities fixed
- [ ] Performance targets met
- [ ] Documentation complete

## Commands Summary

```bash
# Development
npm start          # Start dev server
npm run build      # Production build
npm run lint:js    # Lint JavaScript
npm run format     # Format code

# Testing
npm test          # Run tests
npm run test:watch # Watch mode

# WordPress
wp plugin activate ai-section-builder-modern
wp plugin deactivate ai_section_builder_v2
```

---

Follow these instructions step by step to set up the modern rebuild properly.
# Instructions for New Claude Code Instance

## Welcome! You're about to rebuild the AI Section Builder plugin using modern React.

## Your Mission

Transform a working but outdated jQuery-based WordPress plugin (6000+ lines in one file) into a modern, maintainable React application with proper architecture.

## Current State

This folder (`ai_section_builder_v2`) contains:
- **Working plugin** - Fully functional but with poor architecture
- **Complete documentation** - Everything you need for the rebuild
- **Known issues** - Security vulnerabilities and bugs to fix

## Documentation Files to Read (In Order)

1. **MIGRATION_GUIDE.md** - Your step-by-step instructions
2. **NEW_PROJECT_SETUP.md** - Exact commands to set up the project  
3. **FEATURE_SPECIFICATIONS.md** - All features that must work
4. **CURRENT_ARCHITECTURE.md** - How the current plugin works
5. **MODERN_REBUILD_PLAN.md** - Technical implementation details
6. **COMPONENT_MAPPING.md** - How to convert jQuery to React
7. **BUSINESS_LOGIC.md** - Rules that must be preserved
8. **KNOWN_ISSUES_TO_FIX.md** - Bugs and security issues to fix

## Quick Start

```bash
# 1. Set up new project folder
cd /Users/breonwilliams/Local Sites/ai-section-builder/app/public/wp-content/plugins/
mkdir ai-section-builder-modern
cd ai-section-builder-modern

# 2. Copy this folder as reference (DO NOT MODIFY)
cp -r ../ai_section_builder_v2 ../ai_section_builder_v2_reference

# 3. Follow NEW_PROJECT_SETUP.md for complete setup
```

## Key Files to Reference

When building, constantly refer to these files in `ai_section_builder_v2_reference/`:

- **assets/js/editor/editor.js** - Contains ALL the business logic (6000+ lines)
- **includes/core/class-ajax-handler.php** - How save/load works
- **templates/editor-page.php** - Current HTML structure
- **assets/css/sections/*.css** - Section styles to preserve

## What to Build

### Core Features (Must Have)
✅ All 7 section types (Hero, Features, FAQ, Stats, etc.)
✅ Visual editor with live preview
✅ Drag-and-drop section reordering
✅ Save/load via WordPress REST API
✅ Media library integration
✅ WYSIWYG editor for content
✅ Global color settings
✅ AI content generation

### Improvements (Must Include)
✅ Fix XSS vulnerability in preview rendering
✅ Fix global colors not updating
✅ Add undo/redo functionality
✅ Add loading indicators
✅ Improve performance (50% faster)
✅ Add keyboard shortcuts
✅ Better error handling

## Technology Stack

### Required
- React 18
- Zustand (state management)
- WordPress REST API
- Tailwind CSS
- DOMPurify (XSS prevention)

### Build Tools
- @wordpress/scripts
- Webpack 5
- ESLint + Prettier

## Development Phases

### Week 1: Foundation
- Set up project structure
- Create WordPress admin page
- Build REST API endpoints
- Basic React shell

### Week 2: Core Editor
- Editor layout (sidebars + canvas)
- State management setup
- Section rendering system

### Week 3-4: Section Components  
- Start with Hero section
- Build all 7 section types
- Create form generators
- Implement preview rendering

### Week 5: Features
- Save/load functionality
- Media library integration
- Drag-and-drop
- AI integration

### Week 6: Polish
- Fix all known bugs
- Add animations
- Performance optimization
- Error handling

### Week 7: Testing
- Unit tests
- Integration tests
- User testing
- Bug fixes

## Success Criteria

The new plugin must:
1. ✅ Have feature parity with v2
2. ✅ Fix all security vulnerabilities
3. ✅ Load 50% faster
4. ✅ Be maintainable (modular code)
5. ✅ Work with existing saved data
6. ✅ Pass accessibility standards
7. ✅ Be mobile responsive

## Critical Warnings

### DO NOT:
❌ Copy jQuery code directly - translate the logic
❌ Use global variables - use proper state management
❌ Skip XSS sanitization - security is critical
❌ Forget mobile testing - must be responsive
❌ Break existing data - maintain compatibility

### ALWAYS:
✅ Sanitize user input with DOMPurify
✅ Test each component in isolation
✅ Follow React best practices
✅ Commit frequently with clear messages
✅ Reference the v2 code for business logic

## Testing Your Progress

After each section component:
1. Can you add the section?
2. Can you edit all fields?
3. Does preview update live?
4. Can you save and reload?
5. Does it match v2 functionality?

## Getting Unstuck

When you hit a problem:
1. Check how v2 does it (editor.js)
2. Read the relevant documentation file
3. Look for similar patterns in existing code
4. Test the specific feature in v2
5. Simplify and test in isolation

## Final Checklist

Before considering complete:
- [ ] All 7 section types working
- [ ] Save/load functioning
- [ ] AI integration complete
- [ ] XSS vulnerability fixed
- [ ] Global colors working
- [ ] Drag-drop reordering works
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Keyboard accessible
- [ ] 50% performance improvement
- [ ] All tests passing

## Contact

The original developer has provided comprehensive documentation. Everything you need is in these files. The current working plugin is your reference implementation - study it, understand it, then rebuild it properly.

## Remember

You're not starting from scratch - you have:
1. A fully working reference implementation
2. Complete documentation of every feature
3. Clear technical specifications
4. Known issues already identified
5. Business logic documented

Your job is to rebuild it the right way using modern React while preserving all functionality and fixing known issues.

Good luck! You've got this! 🚀

---

*P.S. The original developer spent weeks building v2 and learned many lessons. This documentation represents all that knowledge. Use it wisely.*
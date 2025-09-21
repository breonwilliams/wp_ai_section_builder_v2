# AI Section Builder - Refactoring Plan

## Overview
This document outlines the complete plan for refactoring the AI Section Builder editor from a monolithic 6000+ line file into a modular, maintainable architecture.

## Current State Analysis

### Problems with Current Architecture
1. **Monolithic Structure**: Single `editor.js` file with 6000+ lines
2. **Global State**: Editor state scattered across multiple variables
3. **Tight Coupling**: Functions directly depend on each other
4. **No Error Boundaries**: Limited error handling and recovery
5. **Mixed Concerns**: UI, state, AJAX, and business logic intertwined
6. **Difficult Testing**: Cannot test individual components
7. **Poor Maintainability**: Hard to find and fix issues

### Current File Structure
```
/assets/js/editor/
├── editor.js (6000+ lines - MONOLITHIC)
├── data-normalizer.js
├── repeater-field.js
└── document-upload.js
```

## Target Architecture

### Proposed Module Structure
```
/assets/js/editor/
├── editor.js (Main orchestrator ~500 lines)
├── modules/
│   ├── StateManager.js     (Centralized state management)
│   ├── AjaxManager.js      (AJAX operations with retry logic)
│   ├── FormManager.js      (Form generation and handling)
│   ├── SectionManager.js   (Section CRUD operations)
│   ├── UIManager.js        (UI controls and updates)
│   ├── DragDropManager.js  (Drag and drop functionality)
│   └── Utils.js           (Shared utilities)
├── data-normalizer.js (keep as-is)
├── repeater-field.js  (keep as-is)
└── document-upload.js (keep as-is)
```

## Detailed Module Specifications

### 1. StateManager.js (~200 lines)
**Purpose**: Centralized state management with observer pattern

**Responsibilities**:
- Maintain single source of truth for editor state
- Provide state getters/setters
- Implement observer pattern for state changes
- Handle state persistence

**Key Functions**:
```javascript
- init()
- getSections()
- setSections(sections)
- getSection(index)
- get(property)
- set(property, value)
- subscribe(property, callback)
- unsubscribe(property, callback)
- isDirty()
- setDirty(value)
- markClean()
```

### 2. AjaxManager.js (~400 lines)
**Purpose**: Handle all AJAX operations with error handling and retry logic

**Responsibilities**:
- Centralize all AJAX requests
- Implement retry logic with exponential backoff
- Handle request queuing
- Manage loading states

**Key Functions**:
```javascript
- request(options)
- saveSections(postId, sections)
- getSectionForm(type, index)
- renderForm(type, shortcode)
- uploadDocument(formData)
- debouncedRequest(key, options, delay)
```

### 3. FormManager.js (~1500 lines)
**Purpose**: Handle all form generation and management

**Critical Note**: THIS MODULE MUST INCLUDE ALL FORM GENERATION FUNCTIONS
- Must copy ALL generateXxxForm() functions from original
- Must handle form display/hiding
- Must manage live preview updates
- Must initialize WYSIWYG editors
- Must handle repeater fields

**Key Functions**:
```javascript
// Form Generation (MUST BE COPIED FROM ORIGINAL)
- generateHeroForm(content)
- generateHeroFormForm(content)
- generateFeaturesForm(content)
- generateChecklistForm(content)
- generateFaqForm(content)
- generateStatsForm(content)
- generateTestimonialsForm(content)

// Form Management
- openForm(type, index)
- displayForm(formHtml)
- closeForm()
- serializeForm()
- validateForm()
- bindFormEvents()

// Repeater Initialization (MUST BE COPIED)
- initGlobalBlocksRepeater(content, sectionType)
- initCardsRepeater(content)
- initChecklistItemsRepeater(content)
- initFaqItemsRepeater(content)
- initStatsRepeater(content)
- initTestimonialsRepeater(content)

// WYSIWYG
- initWYSIWYG(fieldId)
- destroyWYSIWYG(fieldId)
```

### 4. SectionManager.js (~800 lines)
**Purpose**: Handle section CRUD operations and rendering

**Responsibilities**:
- Add/update/delete sections
- Render sections in preview
- Handle section defaults
- Manage section types

**Key Functions**:
```javascript
- addSection(sectionData)
- updateSection(index, updates)
- deleteSection(index)
- duplicateSection(index)
- moveSection(fromIndex, toIndex)
- clearAll()
- renderSection(section, index)
- renderAllSections(container, sections)
- renderEmptyState(container)
- getSectionDefaults(type)
```

### 5. UIManager.js (~700 lines)
**Purpose**: Handle all UI updates and controls

**Responsibilities**:
- Update structure panel
- Show/hide panels
- Display notifications
- Handle keyboard shortcuts
- Manage panel states

**Key Functions**:
```javascript
- init()
- showLibraryPanel()
- showEditPanel()
- showSettingsPanel()
- updateStructurePanel(sections)
- showNotification(message, type, duration)
- toggleSidebars()
- bindKeyboardShortcuts()
```

### 6. DragDropManager.js (~600 lines)
**Purpose**: Handle drag and drop with keyboard fallback

**Responsibilities**:
- Initialize Sortable.js
- Handle drag events
- Provide keyboard navigation fallback
- Update section order

**Key Functions**:
```javascript
- init(options)
- refresh()
- enable()
- disable()
- handleDragStart(e)
- handleDragEnd(e)
- initKeyboardNavigation()
```

### 7. Utils.js (~350 lines)
**Purpose**: Shared utility functions

**Responsibilities**:
- HTML escaping/sanitizing
- URL validation
- Debouncing/throttling
- Deep cloning
- Type checking

**Key Functions**:
```javascript
- escapeHtml(text)
- sanitizeHtml(html, allowedTags)
- isValidUrl(url)
- debounce(func, wait)
- throttle(func, limit)
- deepClone(obj)
- generateId()
- copyToClipboard(text)
```

## Critical Migration Requirements

### 1. Form Flow Must Match Original
**CRITICAL**: The form flow must work EXACTLY like the original:
1. User clicks section type
2. Section is IMMEDIATELY added to state with defaults
3. Form opens for editing the newly added section
4. Live preview shows immediately
5. Changes update the existing section in state

### 2. Functions That MUST Be Migrated Intact

From the original `editor.js`, these functions must be copied with minimal changes:

**Form Generation Functions**:
- `generateHeroForm()`
- `generateHeroFormForm()`
- `generateFeaturesForm()`
- `generateChecklistForm()`
- `generateFaqForm()`
- `generateStatsForm()`
- `generateTestimonialsForm()`
- `generateMediaField()`
- `escapeHtml()`

**Repeater Initialization Functions**:
- `initGlobalBlocksRepeater()`
- `initCardsRepeater()`
- `initChecklistItemsRepeater()`
- `initFaqItemsRepeater()`
- `initStatsRepeater()`
- `initTestimonialsRepeater()`

**Default Values Objects**:
- `heroDefaults`
- `heroFormDefaults`
- `featuresDefaults`
- `checklistDefaults`
- `faqDefaults`
- `statsDefaults`
- `testimonialsDefaults`

### 3. Event Flow That Must Be Preserved

The following jQuery events must continue to work:
- `aisb:form:opened`
- `aisb:form:closed`
- `aisb:form:saved`
- `aisb:sections:changed`
- `aisb:preview:update`
- `aisb:save:requested`

### 4. DOM Structure Dependencies

These element IDs/classes are hardcoded and must be maintained:
- `#aisb-sections-preview` - Main preview container
- `#aisb-edit-content` - Form container
- `#aisb-library-mode` - Library panel
- `#aisb-edit-mode` - Edit panel
- `.aisb-section-type` - Section type buttons
- `#aisb-save-sections` - Save button
- `#aisb-clear-all-sections` - Clear all button

## Implementation Strategy

### Phase 1: Setup Module Structure
1. Create module files with basic structure
2. Set up module loading in Asset Manager
3. Ensure all modules load without errors
4. Create initialization test script

### Phase 2: Migrate State Management
1. Move all state variables to StateManager
2. Replace direct state access with StateManager calls
3. Test state operations work correctly

### Phase 3: Migrate AJAX Operations
1. Move all AJAX calls to AjaxManager
2. Add retry logic and error handling
3. Test save/load operations

### Phase 4: Migrate Form Management (CRITICAL)
1. **Copy ALL form generation functions verbatim**
2. **Copy ALL repeater initialization functions**
3. **Ensure forms display in #aisb-edit-content**
4. **Ensure sections are added to state immediately for new sections**
5. Test each section type creates and edits correctly

### Phase 5: Migrate Section Operations
1. Move section CRUD to SectionManager
2. Move section rendering to SectionManager
3. Test all section operations

### Phase 6: Migrate UI Operations
1. Move panel management to UIManager
2. Move notifications to UIManager
3. Test UI updates work correctly

### Phase 7: Add Drag & Drop
1. Implement DragDropManager
2. Add keyboard navigation fallback
3. Test reordering works

### Phase 8: Wire Everything Together
1. Update main editor.js to orchestrate modules
2. Ensure all event flows work
3. Test complete user workflows

### Phase 9: Add Error Handling
1. Add try-catch blocks around critical operations
2. Implement error boundaries
3. Add recovery mechanisms
4. Test error scenarios

## Testing Checklist

Each phase must pass these tests before proceeding:

### Core Functionality Tests
- [ ] Click section type → Section appears in preview
- [ ] Click section type → Form opens in left panel
- [ ] Edit form field → Live preview updates
- [ ] Toggle theme → Preview updates
- [ ] Toggle layout → Preview updates
- [ ] Click Save → Sections persist
- [ ] Reload page → Sections reload correctly
- [ ] Click Clear All → All sections removed
- [ ] Drag section → Order updates
- [ ] Delete section → Section removed
- [ ] Duplicate section → Section copied

### Section Type Tests
- [ ] Hero section creates and edits
- [ ] Hero Form section creates and edits
- [ ] Features section creates and edits
- [ ] Checklist section creates and edits
- [ ] FAQ section creates and edits
- [ ] Stats section creates and edits
- [ ] Testimonials section creates and edits

### Edge Case Tests
- [ ] Create section with no content
- [ ] Save with validation errors
- [ ] Network failure during save
- [ ] Malformed section data
- [ ] Multiple rapid clicks
- [ ] Browser back/forward

## Common Pitfalls to Avoid

1. **DO NOT** change the form generation HTML structure
2. **DO NOT** change section default values
3. **DO NOT** change the save/load data format
4. **DO NOT** remove jQuery dependencies (WordPress standard)
5. **DO NOT** change CSS class names or IDs
6. **DO NOT** forget to initialize WYSIWYG editors
7. **DO NOT** forget to initialize repeater fields
8. **DO NOT** change the section addition flow (must add to state immediately)

## Success Criteria

The refactoring is complete when:
1. All tests pass
2. No console errors
3. User experience is identical to original
4. Code is modular and maintainable
5. Each module is under 1000 lines (except FormManager)
6. Error handling is comprehensive
7. Performance is equal or better

## Rollback Plan

If refactoring fails:
1. Git restore all changed files
2. Remove new module files
3. Restore original editor.js
4. Document what went wrong
5. Adjust plan and retry

---

**Important**: This refactoring is extensive. Each phase should be completed and tested before moving to the next. The most critical phase is Phase 4 (Form Management) as it contains the most complex logic and user-facing functionality.
# Known Issues to Fix in Modern Rebuild

## Critical Security Issues

### 1. XSS Vulnerability in Preview Rendering ⚠️ CRITICAL
**Location**: editor.js lines 4585-4952  
**Problem**: User input rendered without proper escaping in preview  
**Current Code**:
```javascript
// Dangerous - executes scripts
<div class="aisb-hero__body">${content.content}</div>
```
**Fix Required**:
```javascript
// Safe - sanitizes HTML
<div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(content.content) }} />
```
**Affected Areas**:
- All WYSIWYG content fields
- FAQ answers
- Outro content fields
- Any HTML string concatenation

### 2. CSRF Token Validation
**Location**: AJAX handlers  
**Problem**: Inconsistent nonce verification  
**Fix**: Always verify nonce on server side, use WordPress REST API built-in nonce handling

### 3. SQL Injection Risk
**Location**: PHP save handlers  
**Problem**: Direct database queries without proper escaping  
**Fix**: Use WordPress prepared statements or REST API

## Performance Issues

### 1. Full Preview Re-renders
**Location**: editor.js updatePreview() function  
**Problem**: Entire preview rebuilds on every keystroke  
**Impact**: Laggy typing, poor performance  
**Fix**: Use React's virtual DOM for differential updates

### 2. Large Bundle Size
**Problem**: 6000+ lines loaded at once  
**Impact**: Slow initial load  
**Fix**: Code splitting, lazy loading sections

### 3. jQuery DOM Manipulation
**Problem**: Direct DOM manipulation is slow  
**Examples**:
```javascript
$('#preview').html(fullHTML);  // Replaces entire DOM
$('.section').each(function() { ... });  // Inefficient loops
```
**Fix**: React's efficient rendering

### 4. No Debouncing
**Problem**: Functions fire on every keystroke  
**Fix**: Implement debouncing for:
- Preview updates
- Auto-save
- Validation
- API calls

## Functional Bugs

### 1. Global Colors Not Updating
**Location**: Global settings panel  
**Problem**: Colors don't apply to sections after saving  
**Symptoms**: User changes colors but sections don't reflect changes  
**Root Cause**: CSS variables not properly injected  
**Fix**: Use CSS-in-JS or proper CSS variable injection

### 2. Media Placeholder Issues
**Problem**: Media placeholders don't respect theme variants  
**Fix**: Ensure placeholder styles follow theme

### 3. Section Deletion Confirmation
**Problem**: No confirmation before deleting sections  
**Fix**: Add confirmation modal

### 4. Lost Form Data
**Problem**: Unsaved changes lost when switching sections  
**Fix**: Auto-save drafts or warn before switching

### 5. FAQ Accordion State
**Problem**: All FAQs expand/collapse together  
**Fix**: Individual state management per FAQ item

## UX Issues

### 1. No Undo/Redo
**Problem**: Users can't undo accidental changes  
**Fix**: Implement history management with Zustand

### 2. No Keyboard Shortcuts
**Problem**: Everything requires mouse clicks  
**Fix**: Add shortcuts for:
- Save (Cmd/Ctrl + S)
- Undo (Cmd/Ctrl + Z)
- Redo (Cmd/Ctrl + Shift + Z)
- Delete section (Delete key)
- Duplicate (Cmd/Ctrl + D)

### 3. Poor Mobile Experience
**Problem**: Editor not optimized for mobile/tablet  
**Fix**: Responsive design, touch interactions

### 4. Confusing Empty States
**Problem**: Not clear what to do when no sections  
**Fix**: Better onboarding, helpful empty states

### 5. No Loading Indicators
**Problem**: No feedback during save/load operations  
**Fix**: Loading spinners, progress bars

## Code Quality Issues

### 1. Global Variable Pollution
```javascript
var editorState = { ... };  // Global
var currentSection = null;  // Global
```
**Fix**: Encapsulate in modules/components

### 2. Mixed Concerns
**Problem**: UI, state, and API logic all mixed  
**Example**: Save function also updates UI  
**Fix**: Separate into layers (UI, State, API)

### 3. No Error Boundaries
**Problem**: One error crashes entire editor  
**Fix**: React error boundaries

### 4. Inconsistent Event Handling
```javascript
$(document).on('click', '.btn', ...);  // Some use delegation
$('.btn').click(...);  // Others use direct binding
```
**Fix**: Consistent React event handlers

### 5. Memory Leaks
**Problem**: Event handlers not cleaned up  
**Fix**: Proper cleanup in useEffect

## Accessibility Issues

### 1. Missing ARIA Labels
**Problem**: Screen readers can't understand UI  
**Fix**: Add proper ARIA attributes

### 2. No Focus Management
**Problem**: Focus lost after actions  
**Fix**: Manage focus programmatically

### 3. Color Contrast Issues
**Problem**: Some text hard to read  
**Fix**: WCAG AA compliance

### 4. Keyboard Navigation Broken
**Problem**: Can't navigate with keyboard only  
**Fix**: Proper tab order, keyboard handlers

## Data Management Issues

### 1. No Data Validation
**Problem**: Invalid data can be saved  
**Fix**: Schema validation with Zod or Yup

### 2. No Migration System
**Problem**: Breaking changes affect existing data  
**Fix**: Version tracking and migration scripts

### 3. Lost Draft Changes
**Problem**: Work lost if browser crashes  
**Fix**: Local storage auto-save

### 4. No Conflict Resolution
**Problem**: Multiple users can overwrite each other  
**Fix**: Optimistic locking or real-time collaboration

## WordPress Integration Issues

### 1. Theme Conflicts
**Problem**: Theme styles override plugin styles  
**Fix**: Better CSS isolation, higher specificity

### 2. Plugin Conflicts
**Problem**: JavaScript conflicts with other plugins  
**Fix**: Proper namespacing, no globals

### 3. Incomplete Uninstall
**Problem**: Data left in database after uninstall  
**Fix**: Proper cleanup hooks

## Testing Issues

### 1. No Automated Tests
**Problem**: Manual testing only  
**Fix**: Jest unit tests, React Testing Library

### 2. No E2E Tests
**Problem**: User flows not tested  
**Fix**: Cypress or Playwright tests

### 3. No Visual Regression Tests
**Problem**: Style changes go unnoticed  
**Fix**: Visual regression with Percy

## Priority Fixes for MVP

### High Priority (Security & Data Loss)
1. ✅ XSS vulnerability fix
2. ✅ CSRF protection
3. ✅ Auto-save functionality
4. ✅ Data validation

### Medium Priority (UX)
1. ✅ Undo/redo
2. ✅ Loading indicators
3. ✅ Error handling
4. ✅ Keyboard shortcuts

### Low Priority (Nice to Have)
1. ⏱️ Visual regression testing
2. ⏱️ Real-time collaboration
3. ⏱️ Advanced animations
4. ⏱️ Theme builder

## Estimated Fix Time

- **Security fixes**: 1 day
- **Performance fixes**: 2 days (built into React)
- **Functional bugs**: 2 days
- **UX improvements**: 3 days
- **Accessibility**: 2 days
- **Testing setup**: 3 days

**Total**: ~2 weeks of dedicated bug fixing (included in rebuild time)

---

These issues must be addressed in the modern rebuild to ensure a stable, secure, and user-friendly plugin.
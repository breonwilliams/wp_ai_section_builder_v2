# 🚨 CRITICAL DEVELOPMENT RULES

## ⚠️ MANDATORY: Incremental Development Only

### The Golden Rule
**Never write more than 100-200 lines of code without user testing.**

### What Went Wrong Before
1. **Built entire editor without testing basics** ❌
2. **Used iframe approach (incompatible with drag-drop)** ❌
3. **Created 13 sections before testing one** ❌
4. **Mixed jQuery and native JS incorrectly** ❌
5. **No incremental validation** ❌
6. **Made assumptions about complex features** ❌

### Development Phases (STRICT ORDER)

#### Phase 1: Foundation (TEST BEFORE PROCEEDING)
- [ ] Basic plugin activation
- [ ] ONE section type (Hero only)
- [ ] Simple admin form to add section
- [ ] Save to database
- [ ] Render on frontend
- [ ] **STOP - USER MUST TEST THIS**

#### Phase 2: Expand Minimally (TEST BEFORE PROCEEDING)
- [ ] Add ONE more section type
- [ ] Basic section management (list view)
- [ ] Edit existing sections
- [ ] **STOP - USER MUST TEST THIS**

#### Phase 3: Basic Management (TEST BEFORE PROCEEDING)
- [ ] Section reordering (simple up/down buttons)
- [ ] Delete sections
- [ ] Duplicate sections
- [ ] **STOP - USER MUST TEST THIS**

#### Phase 4: Settings (TEST BEFORE PROCEEDING)
- [ ] Basic section settings panel
- [ ] Global page settings
- [ ] Theme integration
- [ ] **STOP - USER MUST TEST THIS**

#### Phase 5: Advanced Features (ONLY IF ALL ABOVE WORKS)
- [ ] Consider drag-drop interface
- [ ] Consider AI integration
- [ ] Additional section types

### Implementation Rules

#### JavaScript Rules
1. **Use jQuery consistently** - No mixing with native DOM API
2. **Test every function individually**
3. **Use WordPress standards** (wp.hooks, wp.ajax)
4. **No complex state management initially**

#### PHP Rules
1. **Follow WordPress coding standards**
2. **Use WordPress APIs** (Settings API, Meta API)
3. **Proper nonce verification**
4. **Capability checks everywhere**

#### Architecture Rules
1. **Simple procedural code first**
2. **No complex OOP patterns until basics work**
3. **Database schema must be simple and tested**
4. **No service containers until justified**

#### Testing Rules
1. **Test on clean WordPress install**
2. **Test with default theme (Twenty Twenty-Four)**
3. **Test with one popular theme (Astra or GeneratePress)**
4. **User must verify every feature works as expected**

### Red Flags - STOP DEVELOPMENT IF:
- ⛔ Any JavaScript errors in console
- ⛔ Any PHP errors or warnings
- ⛔ Feature doesn't work as user expects
- ⛔ Database queries fail
- ⛔ Frontend doesn't render properly

### The Right Way Forward
1. ✅ Start with absolute minimum viable product
2. ✅ Test every single step with user
3. ✅ User validates before proceeding to next phase
4. ✅ Use only proven, simple patterns
5. ✅ No assumptions - verify everything works

## 📝 Notes for Future Development

### Why This Approach
- Prevents building on broken foundations
- Ensures every feature actually works
- Allows course correction early
- Builds user confidence in the product

### Success Metrics
- User can successfully test each phase
- No JavaScript console errors
- No PHP errors in WordPress debug log
- Features work as expected by user
- Code is maintainable and expandable

**Remember: A simple plugin that works is infinitely better than a complex one that doesn't.**
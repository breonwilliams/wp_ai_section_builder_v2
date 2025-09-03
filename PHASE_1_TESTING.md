# 🧪 Phase 1 Testing Guide

## What to Test

**This is Phase 1: Basic Hero Section Only**

### Expected Functionality
1. ✅ Plugin activates without errors
2. ✅ Meta box appears on posts/pages  
3. ✅ Can add Hero section with form fields
4. ✅ Section saves when post is updated
5. ✅ Hero section displays on frontend
6. ✅ Basic styling is applied

### Testing Steps

#### 1. Plugin Activation
1. Go to Plugins → Installed Plugins
2. Find "AI Section Builder Pro" 
3. Click "Activate"
4. Should activate without any PHP errors

#### 2. Admin Interface  
1. Go to AI Section Builder menu (admin sidebar)
2. Should see Phase 1 status page
3. Go to any post or page → Edit
4. Should see "AI Section Builder" meta box
5. If no sections: Should see Hero section form
6. If section exists: Should see section summary

#### 3. Add Hero Section
1. Fill out Hero section form:
   - **Headline**: "Welcome to My Site"
   - **Subheadline**: "This is a test of the Hero section"  
   - **Button Text**: "Learn More"
   - **Button URL**: "#test"
2. Click "Update" to save post
3. Meta box should now show "Hero Section Added" status

#### 4. Frontend Display (Full-Width Template Override)
1. View the post/page on frontend
2. Hero section should now display FULL-WIDTH:
   - **No page title visible** (hidden by template override)
   - **Purple gradient spans entire viewport width**
   - **Large white headline text** (centered)
   - **Subheadline below it**
   - **Button at bottom**
   - **No content area padding/margins around section**
3. Scroll down - regular page content should appear below sections (if any)
4. Check mobile responsive (resize browser)
5. Verify header/footer are still from your theme (preserved)

#### 5. Template Override Verification  
1. Right-click page → "View Page Source"
2. Should see `<body class="...aisb-canvas aisb-template-override...">`
3. Should see custom CSS removing theme content constraints
4. Should NOT see the page title in HTML (it's hidden)
5. Hero section should be outside theme's content wrapper

### Success Criteria ✅
- [ ] No JavaScript errors in browser console
- [ ] No PHP errors in WordPress debug log
- [ ] Hero section form works and saves data
- [ ] **Hero section displays FULL-WIDTH (spans entire viewport)**
- [ ] **Page title is hidden when sections are active**
- [ ] **Theme header/footer are preserved**
- [ ] Body has `aisb-canvas` CSS class when sections active
- [ ] Mobile responsive layout works
- [ ] Button links work
- [ ] Regular page content appears below sections (if any exists)

### Known Limitations (Phase 1)
- Only ONE Hero section per post
- No editing existing sections (will add in Phase 2)
- No other section types yet
- No drag/drop interface
- No AI features yet

### If Something Doesn't Work
1. Check browser console for JavaScript errors
2. Enable WordPress debug: `WP_DEBUG = true` 
3. Check PHP error logs
4. Verify WordPress version (6.0+) and PHP version (7.4+)

### Ready for Phase 2?
**Only proceed to Phase 2 if ALL Phase 1 tests pass.**

Phase 2 will add:
- Edit existing sections
- One additional section type
- Basic section management

---

**Remember**: This is incremental development. Each phase must be fully tested and working before moving to the next phase.
# 🧪 Builder Conflict Testing Guide

## Overview
This guide covers testing the AI Section Builder's conflict detection and resolution system with major WordPress page builders.

## Prerequisites
- WordPress site with AI Section Builder installed
- Test posts/pages prepared for each scenario
- Access to install other page builders for testing

## Test Scenarios

### 1. No Page Builders (Clean State)
**Setup:**
- Fresh post with no page builder content
- No other page builders active on the post

**Expected Behavior:**
- ✅ Meta box shows "Ready for Page Builder" 
- ✅ Shows blue "Build with AI Section Builder" button
- ✅ No conflict warnings displayed
- ✅ Activation should work immediately

**Test Steps:**
1. Create new post/page
2. Check AI Section Builder meta box
3. Click "Build with AI Section Builder"
4. Verify `_aisb_enabled` meta is set to 1
5. Check frontend renders with template override

---

### 2. Elementor Conflicts

#### 2a. Elementor Content Present
**Setup:**
```php
// Simulate Elementor data
update_post_meta($post_id, '_elementor_data', '[{"elType":"section"}]');
update_post_meta($post_id, '_elementor_edit_mode', 'builder');
```

**Expected Behavior:**
- ⚠️ Meta box shows "Other Page Builder Active"
- ⚠️ Shows "Switch to AI Section Builder" button
- ✅ Lists "Elementor" as the active builder

#### 2b. Switch from Elementor
**Test Steps:**
1. Click "Switch to AI Section Builder" 
2. Confirm switch in dialog
3. Check that `_elementor_edit_mode` is set to empty
4. Verify original content is backed up
5. Check `_aisb_switched_from` contains 'elementor'

---

### 3. Beaver Builder Conflicts

#### 3a. Beaver Builder Active
**Setup:**
```php
update_post_meta($post_id, '_fl_builder_data', 'a:1:{i:0;O:8:"stdClass":1:{s:4:"type";s:3:"row";}}');
update_post_meta($post_id, '_fl_builder_enabled', 1);
```

**Expected Behavior:**
- ⚠️ Shows "Beaver Builder" as active builder
- ⚠️ Switch button available

#### 3b. Switch from Beaver Builder  
**Test Steps:**
1. Switch to AI Section Builder
2. Verify `_fl_builder_enabled` is set to 0
3. Check data is preserved but builder deactivated

---

### 4. Divi Builder Conflicts

#### 4a. Divi Active
**Setup:**
```php
update_post_meta($post_id, '_et_pb_use_builder', 'on');
update_post_meta($post_id, '_et_pb_page_layout', 'et_full_width_page');
```

**Expected Behavior:**
- ⚠️ Shows "Divi Builder" as active
- ⚠️ Switch functionality available

#### 4b. Switch from Divi
**Test Steps:**
1. Switch to AI Section Builder
2. Check `_et_pb_use_builder` is set to 'off'
3. Verify layout is preserved

---

### 5. Multiple Builder Conflicts

#### 5a. Multiple Builders Present
**Setup:**
```php
// Simulate multiple builders
update_post_meta($post_id, '_elementor_data', '[{}]');
update_post_meta($post_id, '_fl_builder_data', 'a:1:{}');
update_post_meta($post_id, '_et_pb_use_builder', 'on');
```

**Expected Behavior:**
- 🚨 Meta box shows "Page Builder Conflict Detected" 
- 🚨 Red warning styling with list of conflicting builders
- 🚨 "Switch to AI Section Builder" button with warning text
- 🚨 Requires confirmation before switching

#### 5b. Resolve Multiple Conflicts
**Test Steps:**
1. Click switch button with confirmation
2. Verify all conflicting builders are deactivated
3. Check `_aisb_switched_from` contains all previous builders
4. Ensure only AI Section Builder is active

---

### 6. AI Section Builder Active States

#### 6a. AISB Already Active (No Conflicts)
**Setup:**
```php
update_post_meta($post_id, '_aisb_enabled', 1);
update_post_meta($post_id, '_aisb_sections', [['type' => 'hero']]);
```

**Expected Behavior:**
- ✅ Shows "AI Section Builder Active" with green styling
- ✅ "Edit with AI Section Builder" button (primary)
- ✅ "Deactivate Builder" button (secondary)
- ✅ No conflict warnings

#### 6b. AISB Active with Conflicts
**Setup:**
```php
update_post_meta($post_id, '_aisb_enabled', 1);
update_post_meta($post_id, '_elementor_data', '[{}]'); // Conflicting data
```

**Expected Behavior:**
- 🚨 Still shows conflict detection
- ⚠️ Template override should be disabled
- ⚠️ Admin notice about conflicts

---

### 7. Template Override Testing

#### 7a. No Conflicts - Override Active
**Test Steps:**
1. Enable AI Section Builder on a post
2. View post frontend
3. Check body class includes `aisb-canvas aisb-template-override`
4. Verify page title is hidden
5. Verify sections render full-width

#### 7b. With Conflicts - Override Disabled  
**Test Steps:**
1. Create post with conflicting builder data
2. Enable AI Section Builder
3. View frontend
4. Verify template override is NOT applied
5. Check admin shows conflict notice

---

### 8. Content Backup & Restoration

#### 8a. Content Backup on Switch
**Test Steps:**
1. Create post with original content
2. Add Elementor content  
3. Switch to AI Section Builder
4. Check `_aisb_original_content` meta contains:
   - Original title
   - Original content 
   - Original excerpt
   - Timestamp

#### 8b. Data Preservation
**Test Steps:**
1. Switch between builders multiple times
2. Verify original data is never lost
3. Check `_aisb_switched_from` tracks builder history
4. Ensure meta data from other builders is preserved (not deleted)

---

### 9. Database Performance Testing

#### 9a. Index Creation
**Test Steps:**
1. Activate plugin 
2. Check database for AISB indexes:
   ```sql
   SHOW INDEX FROM wp_postmeta WHERE Key_name LIKE 'aisb_%';
   ```
3. Should see indexes for: `_aisb_enabled`, `_aisb_sections`, etc.

#### 9b. Cache Testing  
**Test Steps:**
1. Enable AI Section Builder on post
2. Load frontend page multiple times
3. Check performance - subsequent loads should be faster
4. Update post content
5. Verify cache is invalidated properly

---

### 10. Error Scenarios

#### 10a. Invalid Builder Data
**Test Steps:**
1. Manually set invalid meta data
2. Check plugin handles gracefully
3. No PHP errors or warnings
4. Fallback behavior works correctly

#### 10b. Permission Testing
**Test Steps:**
1. Test with different user roles
2. Verify capability checks work
3. Non-editors can't activate builders

---

## Performance Benchmarks

### Query Count Monitoring
- Test pages with/without AISB active
- Monitor database queries using Query Monitor plugin
- AISB should add minimal query overhead

### Load Time Testing
- Test with 0, 1, 5, 10+ posts using AISB
- Measure frontend page load times
- Check for N+1 query problems

---

## Success Criteria ✅

### Conflict Detection
- [ ] All 6 major page builders detected correctly
- [ ] Multiple builder conflicts handled properly
- [ ] Clear user messaging for each scenario

### Safe Switching
- [ ] Original content always preserved
- [ ] Other builder data preserved (not deleted)
- [ ] Switch confirmations work correctly
- [ ] Undo/rollback possible

### Template Override
- [ ] Works when no conflicts present
- [ ] Properly disabled when conflicts exist
- [ ] Admin notices shown for conflicts
- [ ] Full-width rendering works correctly

### Performance
- [ ] Database indexes created successfully
- [ ] Caching reduces repeated queries
- [ ] Cache invalidation works on updates
- [ ] No significant performance degradation

### Error Handling
- [ ] Invalid data handled gracefully
- [ ] No PHP errors under any scenario
- [ ] Proper capability checks enforced
- [ ] Graceful fallbacks for edge cases

---

## Testing Tools

### WordPress Plugins for Testing
- **Query Monitor**: Monitor database performance
- **Debug Bar**: Check for PHP errors/warnings  
- **User Switching**: Test different user capabilities

### Browser Testing
- Chrome DevTools for performance monitoring
- Check console for JavaScript errors
- Network tab for load time analysis

### Database Tools
- phpMyAdmin or similar for direct database inspection
- Check meta data integrity after switches
- Verify index creation and usage

---

## Debugging Tips

### Enable WordPress Debug Mode
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs
- Monitor `wp-content/debug.log` for PHP errors
- Browser console for JavaScript errors
- Server error logs for critical issues

### Performance Debugging
```php
// Add to functions.php temporarily
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        $stats = aisb_get_performance_stats();
        echo '<div style="position:fixed; bottom:0; right:0; background:#000; color:#fff; padding:10px; font-size:11px;">';
        echo 'AISB Stats: ' . json_encode($stats);
        echo '</div>';
    }
});
```

---

**Remember**: Each test scenario should be thoroughly verified before moving to the next phase of development. The conflict resolution system is critical for user experience and plugin adoption.
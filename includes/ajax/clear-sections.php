<?php
/**
 * Clear sections utility
 * 
 * This file helps clear corrupted sections from the database
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clear all sections for a specific post
 * 
 * @param int $post_id The post ID
 * @return bool Success status
 */
function aisb_clear_sections($post_id) {
    if (!$post_id) {
        return false;
    }
    
    // Clear the sections meta
    delete_post_meta($post_id, '_aisb_sections');
    
    // Also clear any transients
    $transient_key = 'aisb_ai_sections_' . $post_id . '_' . get_current_user_id();
    delete_transient($transient_key);
    
    return true;
}

// Add AJAX handler for clearing sections
add_action('wp_ajax_aisb_clear_all_sections', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Check capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Invalid post');
        return;
    }
    
    // Clear sections
    aisb_clear_sections($post_id);
    
    wp_send_json_success('All sections cleared');
});
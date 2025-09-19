<?php
/**
 * Editor AJAX Handler Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling editor-related AJAX requests
 */
class Editor_Ajax {
    
    /**
     * Constructor - Register all AJAX handlers
     */
    public function __construct() {
        $this->register_ajax_handlers();
    }
    
    /**
     * Register all AJAX handlers for editor functionality
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_aisb_activate_builder', array($this, 'handle_activate_builder'));
        add_action('wp_ajax_aisb_deactivate_builder', array($this, 'handle_deactivate_builder'));
        add_action('wp_ajax_aisb_save_sections', array($this, 'handle_save_sections'));
        add_action('wp_ajax_aisb_render_form', array($this, 'handle_render_form'));
    }
    
    /**
     * Handle activate builder AJAX request
     */
    public function handle_activate_builder() {
        // Check nonce - must match the action used in wp_nonce_field
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_meta_box_action')) {
            wp_send_json_error('Security check failed');
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $action = isset($_POST['builder_action']) ? sanitize_text_field($_POST['builder_action']) : '';
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        // Activate the builder
        update_post_meta($post_id, '_aisb_enabled', '1');
        
        // If switching from another builder, deactivate others
        if ($action === 'switch') {
            // Could add logic here to deactivate other builders if needed
        }
        
        // Initialize sections array if not exists
        $sections = get_post_meta($post_id, '_aisb_sections', true);
        if (!is_array($sections)) {
            update_post_meta($post_id, '_aisb_sections', []);
        }
        
        wp_send_json_success(['message' => 'Builder activated']);
    }
    
    /**
     * Handle deactivate builder AJAX request
     */
    public function handle_deactivate_builder() {
        // Check nonce - must match the action used in wp_nonce_field
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_meta_box_action')) {
            wp_send_json_error('Security check failed');
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        // Deactivate the builder but PRESERVE sections data
        update_post_meta($post_id, '_aisb_enabled', '0');
        
        // Important: We do NOT delete _aisb_sections here
        // This preserves the user's work for potential reactivation
        
        // Clear cache for this post
        wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
        
        // Log for debugging
        error_log("AISB: Deactivated via AJAX for post $post_id - sections preserved");
        
        wp_send_json_success([
            'message' => 'Builder deactivated. Your sections have been preserved and will be available if you reactivate.',
            'redirect' => false // No redirect needed, we'll reload the meta box
        ]);
    }
    
    /**
     * Handle save sections AJAX request
     */
    public function handle_save_sections() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $sections = isset($_POST['sections']) ? $_POST['sections'] : '';
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        // Parse and validate sections
        $sections_array = json_decode(stripslashes($sections), true);
        if (!is_array($sections_array)) {
            $sections_array = [];
        }
        
        // Debug: Log what we're saving
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB Saving Sections: ' . print_r($sections_array, true));
        }
        
        // Save sections
        update_post_meta($post_id, '_aisb_sections', $sections_array);
        
        // Clear cache for this post
        delete_transient('aisb_sections_' . $post_id);
        
        wp_send_json_success(['message' => 'Sections saved successfully']);
    }
    
    /**
     * Handle render form AJAX request
     */
    public function handle_render_form() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_type = isset($_POST['form_type']) ? sanitize_text_field($_POST['form_type']) : '';
        $form_shortcode = isset($_POST['form_shortcode']) ? stripslashes($_POST['form_shortcode']) : '';
        
        $html = '';
        
        if ($form_type === 'shortcode' && !empty($form_shortcode)) {
            // Process the shortcode - keep original format
            $html = do_shortcode($form_shortcode);
        }
        
        // If no form content, return placeholder with proper classes
        if (empty($html)) {
            ob_start();
            ?>
            <div class="aisb-form-placeholder">
                <form class="aisb-placeholder-form">
                    <div class="aisb-form-field">
                        <input type="text" placeholder="Name" disabled class="aisb-form-input">
                    </div>
                    <div class="aisb-form-field">
                        <input type="email" placeholder="Email" disabled class="aisb-form-input">
                    </div>
                    <div class="aisb-form-field">
                        <input type="tel" placeholder="Phone" disabled class="aisb-form-input">
                    </div>
                    <div class="aisb-form-field">
                        <textarea placeholder="Message" disabled class="aisb-form-textarea" rows="4"></textarea>
                    </div>
                    <div class="aisb-form-field">
                        <button type="button" class="aisb-btn aisb-btn-primary" disabled>Submit</button>
                    </div>
                </form>
            </div>
            <?php
            $html = ob_get_clean();
        }
        
        wp_send_json_success(['html' => $html, 'has_scripts' => strpos($html, '<script') !== false]);
    }
}
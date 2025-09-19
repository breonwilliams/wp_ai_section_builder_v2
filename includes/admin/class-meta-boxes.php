<?php
/**
 * Meta Boxes Management Class
 *
 * @package AISB
 * @subpackage Admin
 */

namespace AISB\Admin;

/**
 * Class Meta_Boxes
 * 
 * Handles all meta box functionality for the AI Section Builder plugin.
 * This includes registering, rendering, and saving meta box data for posts and pages.
 */
class Meta_Boxes {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box']);
    }

    /**
     * Add meta boxes to posts and pages
     */
    public function add_meta_boxes() {
        $post_types = ['post', 'page'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'aisb_sections',
                __('AI Section Builder', 'ai-section-builder'),
                [$this, 'meta_box_callback'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Meta box content - Conflict-aware page builder activation
     * 
     * @param \WP_Post $post Current post object
     */
    public function meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('aisb_meta_box_action', 'aisb_nonce');
        
        // Detect active page builders
        $active_builders = aisb_detect_active_builders($post->ID);
        $conflict_check = aisb_check_conflicts($post->ID);
        $is_aisb_enabled = aisb_is_enabled($post->ID);
        
        ?>
        <div class="aisb-meta-box">
            <div class="aisb-meta-box__inner">
                
                <!-- Builder Detection Status -->
                <?php if (empty($active_builders)): ?>
                    <div class="aisb-status-card aisb-status-ready">
                        <div class="aisb-status-card__header">
                            <span class="aisb-status-card__icon">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                            <span>Ready for Page Builder</span>
                        </div>
                        <div class="aisb-status-card__content">
                            <p>No page builders detected. You can activate AI Section Builder.</p>
                        </div>
                    </div>
                <?php elseif ($is_aisb_enabled && !$conflict_check['has_conflicts']): ?>
                    <div class="aisb-status-card aisb-status-active">
                        <div class="aisb-status-card__header">
                            <span class="aisb-status-card__icon">
                                <span class="dashicons dashicons-admin-customizer"></span>
                            </span>
                            <span>AI Section Builder Active</span>
                        </div>
                        <div class="aisb-status-card__content">
                            <p>This page is using AI Section Builder for layout.</p>
                        </div>
                    </div>
                <?php elseif ($conflict_check['has_conflicts']): ?>
                    <div class="aisb-status-card aisb-status-error">
                        <div class="aisb-status-card__header">
                            <span class="aisb-status-card__icon">
                                <span class="dashicons dashicons-warning"></span>
                            </span>
                            <span>Page Builder Conflict Detected</span>
                        </div>
                        <div class="aisb-status-card__content">
                            <p>Active builders on this page:</p>
                            <ul class="aisb-status-card__list">
                                <?php foreach ($active_builders as $builder): ?>
                                    <li><?php echo esc_html(aisb_get_builder_name($builder)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="aisb-status-card aisb-status-neutral">
                        <div class="aisb-status-card__header">
                            <span class="aisb-status-card__icon">
                                <span class="dashicons dashicons-media-document"></span>
                            </span>
                            <span>Other Page Builder Active</span>
                        </div>
                        <div class="aisb-status-card__content">
                            <p>This page is using:</p>
                            <ul class="aisb-status-card__list">
                                <?php foreach ($active_builders as $builder): ?>
                                    <li><strong><?php echo esc_html(aisb_get_builder_name($builder)); ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="aisb-meta-box__actions">
                    <?php if (empty($active_builders)): ?>
                        <!-- No builders - direct activation -->
                        <button type="button" class="aisb-btn aisb-btn-primary aisb-activate-builder" 
                                data-action="activate" data-post-id="<?php echo $post->ID; ?>">
                            <span class="dashicons dashicons-layout"></span>
                            <?php _e('Build with AI Section Builder', 'ai-section-builder'); ?>
                        </button>
                        <p class="aisb-meta-box__description">
                            Create a custom page layout using AI-powered sections and design tools.
                        </p>
                    
                    <?php elseif ($is_aisb_enabled && !$conflict_check['has_conflicts']): ?>
                        <!-- AISB active - edit button -->
                        <button type="button" class="aisb-btn aisb-btn-primary aisb-edit-builder" 
                                data-post-id="<?php echo $post->ID; ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Edit with AI Section Builder', 'ai-section-builder'); ?>
                        </button>
                        
                        <button type="button" class="aisb-btn aisb-btn-secondary aisb-deactivate-builder" 
                                data-action="deactivate" data-post-id="<?php echo $post->ID; ?>">
                            <?php _e('Deactivate Builder', 'ai-section-builder'); ?>
                        </button>
                    
                <?php elseif ($conflict_check['has_conflicts']): ?>
                    <!-- Conflicts - show warning and switch option -->
                    <button type="button" class="aisb-btn aisb-btn-danger aisb-switch-builder" 
                            data-action="switch" data-post-id="<?php echo $post->ID; ?>"
                            onclick="return aisb_confirm_switch();">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Switch to AI Section Builder', 'ai-section-builder'); ?>
                    </button>
                    <p class="aisb-meta-box__description aisb-text-danger">
                        <strong>Warning:</strong> Switching builders will preserve your original content but deactivate other page builders on this page.
                    </p>
                    
                <?php else: ?>
                    <!-- Other builder active - switch option -->
                    <button type="button" class="aisb-btn aisb-btn-secondary aisb-switch-builder" 
                            data-action="switch" data-post-id="<?php echo $post->ID; ?>"
                            onclick="return aisb_confirm_switch();">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Switch to AI Section Builder', 'ai-section-builder'); ?>
                    </button>
                    <p class="aisb-meta-box__description">
                        Switch from <?php echo esc_html(aisb_get_builder_name($active_builders[0])); ?> to AI Section Builder. Your original content will be preserved.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Hidden fields for AJAX -->
            <input type="hidden" id="aisb-action" name="aisb_action" value="" />
            <input type="hidden" id="aisb-enabled" name="aisb_enabled" value="<?php echo $is_aisb_enabled ? '1' : '0'; ?>" />
        </div>
        
        <script>
            function aisb_confirm_switch() {
                return confirm('Are you sure you want to switch to AI Section Builder? This will deactivate other page builders on this page, but your original content will be preserved.');
            }
            
            jQuery(document).ready(function($) {
                $('.aisb-activate-builder, .aisb-switch-builder, .aisb-deactivate-builder').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var $button = $(this);
                    
                    // Prevent double-clicking
                    if ($button.prop('disabled')) {
                        return false;
                    }
                    
                    var action = $button.data('action');
                    var postId = $button.data('post-id');
                    
                    console.log('AISB: Button clicked', {action: action, postId: postId});
                    
                    // If activate or switch, redirect to editor
                    if (action === 'activate' || action === 'switch') {
                        // Store original button text
                        var originalText = $button.text();
                        
                        // Show loading state immediately
                        $button.prop('disabled', true).text('Loading editor...');
                        
                        // First save the activation state via AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aisb_activate_builder',
                                post_id: postId,
                                builder_action: action,
                                nonce: $('[name="aisb_nonce"]').val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Small delay to ensure database write completes, then redirect to editor
                                    setTimeout(function() {
                                        var adminUrl = <?php echo json_encode(admin_url('admin.php')); ?>;
                                        var editorUrl = adminUrl + '?page=aisb-editor&post_id=' + postId;
                                        console.log('AISB: Redirecting to:', editorUrl);
                                        window.location.href = editorUrl;
                                    }, 100);
                                } else {
                                    alert('Error activating builder: ' + response.data);
                                    $button.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function() {
                                alert('Error activating builder. Please try again.');
                                $button.prop('disabled', false).text(originalText);
                            }
                        });
                        
                        return;
                    }
                    
                    // For deactivate, use AJAX like activate
                    if (action === 'deactivate') {
                        // Store original button text
                        var originalText = $button.text();
                        
                        // Confirm deactivation
                        if (!confirm('Are you sure you want to deactivate AI Section Builder? Your sections will be preserved and can be restored if you reactivate.')) {
                            return false;
                        }
                        
                        // Show loading state
                        $button.prop('disabled', true).text('Deactivating...');
                        
                        // Make AJAX request to deactivate
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aisb_deactivate_builder',
                                post_id: postId,
                                nonce: $('[name="aisb_nonce"]').val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Show success message
                                    console.log('AISB: Builder deactivated successfully');
                                    
                                    // Update button text temporarily to show success
                                    $button.text('Deactivated!');
                                    
                                    // Reload the page after a short delay to update the meta box
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    alert('Error deactivating builder: ' + response.data);
                                    $button.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function() {
                                alert('Error deactivating builder. Please try again.');
                                $button.prop('disabled', false).text(originalText);
                            }
                        });
                        
                        return;
                    }
                });
                
                $('.aisb-edit-builder').on('click', function() {
                    var postId = $(this).data('post-id');
                    var adminUrl = <?php echo json_encode(admin_url('admin.php')); ?>;
                    var editorUrl = adminUrl + '?page=aisb-editor&post_id=' + postId;
                    window.location.href = editorUrl;
                });
            });
        </script>
        <?php
    }

    /**
     * Save meta box data - Handle page builder activation/deactivation
     * 
     * @param int $post_id Post ID
     */
    public function save_meta_box($post_id) {
        // Add debug logging
        error_log("AISB: aisb_save_meta_box called for post $post_id");
        error_log("AISB: POST data: " . print_r($_POST, true));
        
        // Security checks
        if (!isset($_POST['aisb_nonce']) || !wp_verify_nonce($_POST['aisb_nonce'], 'aisb_meta_box_action')) {
            error_log("AISB: Nonce check failed");
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log("AISB: Skipping autosave");
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            error_log("AISB: User capability check failed");
            return;
        }
        
        // Check if we have an action to process
        if (!isset($_POST['aisb_action']) || !isset($_POST['aisb_enabled'])) {
            error_log("AISB: No action or enabled field found");
            return;
        }
        
        $action = sanitize_text_field($_POST['aisb_action']);
        $enabled = sanitize_text_field($_POST['aisb_enabled']);
        
        error_log("AISB: Processing action: $action, enabled: $enabled");
        
        switch ($action) {
            case 'activate':
                // Direct activation - no other builders detected
                update_post_meta($post_id, '_aisb_enabled', 1);
                $this->backup_original_content($post_id);
                
                // IMPORTANT: Clear any old sections data from Phase 1A
                // Phase 1B doesn't create sections - that's for Phase 2
                delete_post_meta($post_id, '_aisb_sections');
                
                // Clear cache and add success notice
                wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
                wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
                set_transient('aisb_activated_' . $post_id, true, 60);
                error_log("AISB: Activated for post $post_id - cleared old sections");
                break;
                
            case 'switch':
                // Switch from another builder - backup and switch
                $this->backup_original_content($post_id);
                $switched_from = $this->deactivate_other_builders($post_id);
                update_post_meta($post_id, '_aisb_enabled', 1);
                
                // IMPORTANT: Clear any old sections data from Phase 1A
                delete_post_meta($post_id, '_aisb_sections');
                
                // Clear cache and add success notice
                wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
                wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
                set_transient('aisb_switched_' . $post_id, $switched_from, 60);
                error_log("AISB: Switched for post $post_id from: " . implode(', ', $switched_from));
                break;
                
            case 'deactivate':
                // Deactivate AI Section Builder but PRESERVE sections
                error_log("AISB: Deactivating for post $post_id - preserving sections");
                
                update_post_meta($post_id, '_aisb_enabled', 0);
                
                // Important: We do NOT delete _aisb_sections anymore
                // This preserves the user's work for potential reactivation
                
                // Clear cache for this post
                wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
                
                // Add admin notice for successful deactivation
                set_transient('aisb_deactivated_' . $post_id, true, 60);
                
                error_log("AISB: Deactivated for post $post_id - sections preserved");
                break;
        }
    }

    /**
     * Backup original content before switching builders
     * 
     * @param int $post_id Post ID
     */
    private function backup_original_content($post_id) {
        $post = get_post($post_id);
        if ($post && !get_post_meta($post_id, '_aisb_original_content', true)) {
            update_post_meta($post_id, '_aisb_original_content', [
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'date' => current_time('mysql')
            ]);
        }
    }

    /**
     * Deactivate other page builders on this post
     * 
     * @param int $post_id Post ID
     * @return array Array of builders that were deactivated
     */
    private function deactivate_other_builders($post_id) {
        // Store which builder we're switching from for reference
        $active_builders = aisb_detect_active_builders($post_id);
        $other_builders = array_diff($active_builders, ['aisb']);
        
        if (!empty($other_builders)) {
            update_post_meta($post_id, '_aisb_switched_from', $other_builders);
        }
        
        // Deactivate other builders by removing their meta keys
        // Note: We're not deleting the data, just the activation flags
        
        // Elementor
        if (get_post_meta($post_id, '_elementor_edit_mode', true)) {
            update_post_meta($post_id, '_elementor_edit_mode', '');
        }
        
        // Beaver Builder  
        if (get_post_meta($post_id, '_fl_builder_enabled', true)) {
            update_post_meta($post_id, '_fl_builder_enabled', 0);
        }
        
        // Divi
        if (get_post_meta($post_id, '_et_pb_use_builder', true) === 'on') {
            update_post_meta($post_id, '_et_pb_use_builder', 'off');
        }
        
        return $other_builders;
    }
}
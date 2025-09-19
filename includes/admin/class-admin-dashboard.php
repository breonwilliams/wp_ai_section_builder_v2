<?php
/**
 * Admin Dashboard Page
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Dashboard class
 */
class Admin_Dashboard {
    
    /**
     * Render the dashboard page
     */
    public function render() {
        ?>
        <div class="aisb-admin-wrap">
            <div class="aisb-admin-header">
                <h1 class="aisb-admin-header__title"><?php _e('AI Section Builder Pro', 'ai-section-builder'); ?></h1>
                <p class="aisb-admin-header__subtitle"><?php _e('Create Beautiful Page Sections with Visual Editor', 'ai-section-builder'); ?></p>
            </div>
            
            <!-- Welcome Section -->
            <div class="aisb-section-card">
                <div class="aisb-section-card__header">
                    <div class="aisb-section-card__icon">
                        <span class="dashicons dashicons-admin-home"></span>
                    </div>
                    <h2 class="aisb-section-card__title">Welcome to AI Section Builder</h2>
                </div>
                <div class="aisb-section-card__content">
                    <p>Create stunning page sections with our intuitive visual editor. Build professional layouts without writing code.</p>
                    
                    <h3>Available Section Types:</h3>
                    <ul>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Hero Sections</strong> - Eye-catching headers with multiple layouts</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Hero with Form</strong> - Hero section with integrated form area</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Features Grid</strong> - Showcase your services or products</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Testimonials</strong> - Display customer reviews and feedback</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>FAQ Sections</strong> - Answer common questions</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Statistics</strong> - Show impressive numbers and metrics</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Checklists</strong> - Present information in organized lists</li>
                    </ul>
                    
                    <div style="margin-top: 20px;">
                        <p><strong>Ready to get started?</strong> Edit any page or post and click "Activate AI Section Builder" to begin creating beautiful sections.</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="aisb-section-card">
                <div class="aisb-section-card__header">
                    <div class="aisb-section-card__icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>
                    <h2 class="aisb-section-card__title">Quick Actions</h2>
                </div>
                <div class="aisb-section-card__content">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?php echo admin_url('admin.php?page=ai-section-builder-editor'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-edit" style="margin-right: 5px; margin-top: 3px;"></span>
                            Open Section Editor
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button">
                            <span class="dashicons dashicons-admin-page" style="margin-right: 5px; margin-top: 3px;"></span>
                            View Pages
                        </a>
                        <a href="<?php echo admin_url('edit.php'); ?>" class="button">
                            <span class="dashicons dashicons-admin-post" style="margin-right: 5px; margin-top: 3px;"></span>
                            View Posts
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Usage Statistics -->
            <div class="aisb-section-card">
                <div class="aisb-section-card__header">
                    <div class="aisb-section-card__icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <h2 class="aisb-section-card__title">Debug Information</h2>
                </div>
                <div class="aisb-section-card__content">
                    <?php $this->render_debug_info(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render debug information
     */
    private function render_debug_info() {
        global $wpdb;
        $posts_with_sections = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_aisb_sections'");
        $posts_enabled = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_aisb_enabled' AND meta_value = '1'");
        ?>
        <ul>
            <li>Posts with sections data: <?php echo intval($posts_with_sections); ?></li>
            <li>Posts with AISB enabled: <?php echo intval($posts_enabled); ?></li>
            <li>WordPress Version: <?php echo get_bloginfo('version'); ?></li>
            <li>PHP Version: <?php echo phpversion(); ?></li>
            <li>Active Theme: <?php echo wp_get_theme()->get('Name'); ?></li>
        </ul>
        
        <p><strong>To debug a specific page:</strong> Add <code>?aisb_debug=1</code> to any page URL on the frontend.</p>
        <?php
    }
}
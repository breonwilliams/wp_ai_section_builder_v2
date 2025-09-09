<?php
/**
 * Plugin Name: AI Section Builder Pro
 * Plugin URI: https://github.com/breonwilliams/ai-section-builder
 * Description: AI-powered section builder for WordPress. Build beautiful pages with pre-designed sections or generate content from documents using AI.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Breon Williams
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-section-builder
 * Network: false
 * 
 * @package AISectiionBuilder
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AISB_VERSION', '2.0.0');
define('AISB_PLUGIN_FILE', __FILE__);
define('AISB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AISB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AISB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * PHASE 1: Basic Plugin Foundation
 * 
 * This is the absolute minimum to:
 * 1. Activate the plugin
 * 2. Add ONE section type (Hero)
 * 3. Simple admin interface
 * 4. Save/render functionality
 * 
 * DO NOT ADD MORE FEATURES UNTIL USER TESTS THIS WORKS
 */

// Initialize plugin
add_action('plugins_loaded', 'aisb_init');

function aisb_init() {
    // Load text domain for translations
    load_plugin_textdomain('ai-section-builder', false, dirname(AISB_PLUGIN_BASENAME) . '/languages');
    
    // Run migration to clean up old Phase 1A data
    aisb_migrate_cleanup_old_data();
    
    // Initialize plugin
    aisb_setup();
}

function aisb_setup() {
    // Load Color Settings class
    require_once AISB_PLUGIN_DIR . 'includes/class-aisb-color-settings.php';
    
    // Hook into WordPress
    add_action('init', 'aisb_register_post_type');
    add_action('admin_menu', 'aisb_add_admin_menu');
    add_action('add_meta_boxes', 'aisb_add_meta_boxes');
    add_action('save_post', 'aisb_save_meta_box');
    
    // Page builder functionality - override templates (late priority to avoid conflicts)
    add_filter('template_include', 'aisb_template_override', 999);
    add_filter('body_class', 'aisb_body_class');
    
    // Admin notices for conflicts
    add_action('admin_notices', 'aisb_admin_notices');
    
    // Visual editor page
    add_action('admin_menu', 'aisb_add_editor_page');
    add_action('admin_init', 'aisb_handle_editor_redirect');
    
    // REST API endpoint for page/post search
    add_action('rest_api_init', 'aisb_register_rest_routes');
    
    // Enqueue styles with high priority (99) to ensure they load after theme styles
    add_action('wp_enqueue_scripts', 'aisb_enqueue_styles', 99);
    add_action('admin_enqueue_scripts', 'aisb_enqueue_admin_styles');
    
    // Performance optimizations
    add_action('save_post', 'aisb_clear_cache_on_save', 20);
    add_action('delete_post', 'aisb_clear_cache_on_delete');
    
    // AJAX handlers
    add_action('wp_ajax_aisb_activate_builder', 'aisb_ajax_activate_builder');
    add_action('wp_ajax_aisb_deactivate_builder', 'aisb_ajax_deactivate_builder');
    add_action('wp_ajax_aisb_save_sections', 'aisb_ajax_save_sections');
}

/**
 * Register custom post type for sections library (future use)
 */
function aisb_register_post_type() {
    // We'll add this later when needed
    // For now, we'll just use post meta
}

/**
 * Add admin menu
 */
function aisb_add_admin_menu() {
    add_menu_page(
        __('AI Section Builder', 'ai-section-builder'),
        __('AI Section Builder', 'ai-section-builder'),
        'manage_options',
        'ai-section-builder',
        'aisb_admin_page',
        'dashicons-layout',
        30
    );
    
    // Add editor submenu
    add_submenu_page(
        'ai-section-builder',
        __('Section Editor', 'ai-section-builder'),
        __('Section Editor', 'ai-section-builder'),
        'edit_posts',
        'ai-section-builder-editor',
        'aisb_editor_page'
    );
}

/**
 * Simple admin page
 */
function aisb_admin_page() {
    // Handle cleanup action
    $cleanup_message = '';
    if (isset($_POST['aisb_cleanup_nonce']) && wp_verify_nonce($_POST['aisb_cleanup_nonce'], 'aisb_cleanup_action')) {
        if (isset($_POST['cleanup_post_id']) && !empty($_POST['cleanup_post_id'])) {
            $post_id = intval($_POST['cleanup_post_id']);
            
            // Clean up all AISB meta data for this post
            delete_post_meta($post_id, '_aisb_enabled');
            delete_post_meta($post_id, '_aisb_sections');
            delete_post_meta($post_id, '_aisb_original_content');
            delete_post_meta($post_id, '_aisb_switched_from');
            
            // Clear cache
            wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
            wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
            
            $cleanup_message = '<div class="aisb-notice aisb-notice-success"><p>Successfully cleaned up AISB data for post ID ' . $post_id . '</p></div>';
        } elseif (isset($_POST['cleanup_all'])) {
            // Clean up all AISB data globally
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_aisb_%'");
            
            // Clear all cache
            wp_cache_flush();
            
            $cleanup_message = '<div class="aisb-notice aisb-notice-success"><p>Successfully cleaned up all AISB data from database.</p></div>';
        }
    }
    ?>
    <div class="aisb-admin-wrap">
        <div class="aisb-admin-header">
            <h1 class="aisb-admin-header__title"><?php _e('AI Section Builder Pro', 'ai-section-builder'); ?></h1>
            <p class="aisb-admin-header__subtitle"><?php _e('Professional Section Builder for WordPress', 'ai-section-builder'); ?></p>
        </div>
        
        <?php echo $cleanup_message; ?>
        
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-rocket"></span>
                </div>
                <h2 class="aisb-section-card__title">Phase 1B: Professional Foundation</h2>
            </div>
            <div class="aisb-section-card__content">
                <p><strong>Current Status:</strong> Page builder conflict detection and activation system</p>
            
                <h3>Features Implemented:</h3>
                <ul>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Smart page builder detection (6 major builders)</li>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Conflict-aware activation system</li>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Safe template override with priority 999</li>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Content backup and restoration</li>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Professional meta box interface</li>
                <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Database performance optimizations</li>
                </ul>
                
                <div class="aisb-status-card aisb-status-warning">
                    <div class="aisb-status-card__header">
                        <span class="aisb-status-card__icon">
                            <span class="dashicons dashicons-warning"></span>
                        </span>
                        <span>Important</span>
                    </div>
                    <div class="aisb-status-card__content">
                        Phase 1B focuses on infrastructure. Visual editor coming in Phase 2.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Manual Cleanup Tool -->
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-trash"></span>
                </div>
                <h2 class="aisb-section-card__title">Manual Cleanup Tool</h2>
            </div>
            <div class="aisb-section-card__content">
                <p>Use this tool to manually remove AISB data if the deactivate button isn't working properly.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('aisb_cleanup_action', 'aisb_cleanup_nonce'); ?>
                
                <h3>Clean Specific Post</h3>
                <table class="aisb-form-table">
                    <tr>
                        <th><label for="cleanup_post_id">Post/Page ID:</label></th>
                        <td>
                            <input type="number" name="cleanup_post_id" id="cleanup_post_id" placeholder="Enter post ID" />
                            <button type="submit" class="aisb-btn aisb-btn-secondary">Clean This Post</button>
                            <p class="aisb-form-description">Enter the ID of the post/page to clean up AISB data.</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Clean All Data</h3>
                <p class="aisb-text-danger"><strong>Warning:</strong> This will remove ALL AI Section Builder data from all posts!</p>
                <button type="submit" name="cleanup_all" value="1" class="aisb-btn aisb-btn-danger" 
                        onclick="return confirm('Are you sure you want to remove ALL AI Section Builder data? This cannot be undone!');">
                    Clean All AISB Data
                </button>
            </form>
            </div>
        </div>
        
        <!-- Debug Information -->
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <h2 class="aisb-section-card__title">Debug Information</h2>
            </div>
            <div class="aisb-section-card__content">
                <?php
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
            </div>
        </div>
    </div>
    <?php
}

/**
 * Placeholder editor page - Phase 1B
 */
function aisb_editor_page() {
    // Get post ID if provided
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    $post = $post_id ? get_post($post_id) : null;
    
    ?>
    <div class="wrap">
        <h1><?php _e('AI Section Builder - Visual Editor', 'ai-section-builder'); ?></h1>
        
        <?php if ($post): ?>
            <div style="background: #e7f3ff; border: 1px solid #0073aa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;">🚧 <?php _e('Editor Coming Soon', 'ai-section-builder'); ?></h2>
                <p><strong><?php _e('Editing:', 'ai-section-builder'); ?></strong> <?php echo esc_html($post->post_title); ?></p>
                <p><?php _e('The visual editor interface is currently under development. This is where you will:', 'ai-section-builder'); ?></p>
                
                <ul style="margin-left: 20px;">
                    <li><?php _e('Drag and drop sections to build your page', 'ai-section-builder'); ?></li>
                    <li><?php _e('Customize section content and styling', 'ai-section-builder'); ?></li>
                    <li><?php _e('Use AI to generate content from documents', 'ai-section-builder'); ?></li>
                    <li><?php _e('Preview changes in real-time', 'ai-section-builder'); ?></li>
                </ul>
                
                <h3><?php _e('Current Development Phase', 'ai-section-builder'); ?></h3>
                <div style="background: #fff; padding: 15px; border-radius: 4px; border-left: 3px solid #00a32a;">
                    <p><strong><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Phase 1B Complete:</strong></p>
                    <ul style="margin-left: 20px;">
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Smart page builder conflict detection</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Safe template override system</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Professional activation workflow</li>
                    </ul>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 3px solid #dba617; margin-top: 10px;">
                    <p><strong>🚧 Next Phase:</strong></p>
                    <ul style="margin-left: 20px;">
                        <li>🔨 Professional file structure</li>
                        <li>🔨 Dark theme editor interface design</li>
                        <li>🔨 Hero section variants (light/dark, 3 layouts)</li>
                        <li>🔨 Frontend-first development approach</li>
                    </ul>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-primary">
                        <span class="dashicons dashicons-arrow-left-alt" style="margin-right: 5px;"></span>
                        <?php _e('Back to Post Editor', 'ai-section-builder'); ?>
                    </a>
                    
                    <?php if (aisb_is_enabled($post_id)): ?>
                        <a href="<?php echo get_permalink($post_id); ?>" class="button" target="_blank" style="margin-left: 10px;">
                            <span class="dashicons dashicons-visibility" style="margin-right: 5px;"></span>
                            <?php _e('View Page', 'ai-section-builder'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <div style="background: #fff3cd; border: 1px solid #dba617; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><span class="dashicons dashicons-warning" style="color: #dba617;"></span> <?php _e('No Post Selected', 'ai-section-builder'); ?></h2>
                <p><?php _e('To use the AI Section Builder editor, please:', 'ai-section-builder'); ?></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Go to any page or post in WordPress', 'ai-section-builder'); ?></li>
                    <li><?php _e('Look for the "AI Section Builder" meta box', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Build with AI Section Builder" to activate', 'ai-section-builder'); ?></li>
                    <li><?php _e('Then click "Edit with AI Section Builder" to open this editor', 'ai-section-builder'); ?></li>
                </ol>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-page" style="margin-right: 5px;"></span>
                        <?php _e('Go to Pages', 'ai-section-builder'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php'); ?>" class="button" style="margin-left: 10px;">
                        <span class="dashicons dashicons-admin-post" style="margin-right: 5px;"></span>
                        <?php _e('Go to Posts', 'ai-section-builder'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Development Progress -->
        <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3><?php _e('Development Progress', 'ai-section-builder'); ?></h3>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #00a32a;"><span class="dashicons dashicons-yes"></span> Completed Features</h4>
                    <ul>
                        <li>Plugin foundation & activation</li>
                        <li>Page builder conflict detection</li>
                        <li>Template override system</li>
                        <li>Content backup & restoration</li>
                        <li>Professional activation workflow</li>
                    </ul>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #dba617;">🚧 In Development</h4>
                    <ul>
                        <li>Visual editor interface</li>
                        <li>Hero section variants</li>
                        <li>Frontend design system</li>
                        <li>Section management</li>
                    </ul>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #666;">📋 Planned Features</h4>
                    <ul>
                        <li>13 section types</li>
                        <li>AI document processing</li>
                        <li>Template library</li>
                        <li>Global settings</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Add hidden editor page for visual builder
 */
function aisb_add_editor_page() {
    add_submenu_page(
        null, // No parent menu - hidden page
        __('AI Section Builder Editor', 'ai-section-builder'),
        __('Editor', 'ai-section-builder'),
        'edit_posts',
        'aisb-editor',
        'aisb_render_editor_page'
    );
}

/**
 * Handle redirect to editor when clicking "Build with AI Section Builder"
 */
function aisb_handle_editor_redirect() {
    if (isset($_GET['aisb_edit']) && isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        
        // Verify user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('You do not have permission to edit this post.', 'ai-section-builder'));
        }
        
        // Redirect to editor page with post ID
        wp_redirect(admin_url('admin.php?page=aisb-editor&post_id=' . $post_id));
        exit;
    }
}

/**
 * Render the visual editor page
 */
function aisb_render_editor_page() {
    // Get post ID from URL
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!$post_id) {
        wp_die(__('No post selected for editing.', 'ai-section-builder'));
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_die(__('Post not found.', 'ai-section-builder'));
    }
    
    // Get existing sections
    $sections = get_post_meta($post_id, '_aisb_sections', true);
    if (!is_array($sections)) {
        $sections = [];
    }
    ?>
    <div class="aisb-editor-wrapper">
        <!-- Editor Toolbar -->
        <div class="aisb-editor-toolbar">
            <div class="aisb-editor-toolbar__left">
                <a href="<?php echo get_edit_post_link($post_id); ?>" class="aisb-editor-btn aisb-editor-btn-ghost">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Editor', 'ai-section-builder'); ?>
                </a>
            </div>
            <div class="aisb-editor-toolbar__center">
                <h1 class="aisb-editor-title">
                    <?php echo esc_html($post->post_title); ?>
                </h1>
            </div>
            <div class="aisb-editor-toolbar__right">
                <div class="aisb-toolbar-group">
                    <button class="aisb-editor-btn aisb-editor-btn-ghost aisb-sidebar-toggle active" 
                            id="aisb-toggle-sidebars" 
                            title="<?php _e('Toggle Sidebars (Shift+S)', 'ai-section-builder'); ?>"
                            aria-label="<?php _e('Toggle sidebars visibility', 'ai-section-builder'); ?>"
                            aria-pressed="true">
                        <span class="dashicons dashicons-editor-contract"></span>
                        <span class="aisb-btn-label"><?php _e('Hide Panels', 'ai-section-builder'); ?></span>
                    </button>
                </div>
                <button class="aisb-editor-btn aisb-editor-btn-primary" id="aisb-save-sections">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save', 'ai-section-builder'); ?>
                </button>
            </div>
        </div>
        
        <!-- Main Editor Layout -->
        <div class="aisb-editor-layout">
            <!-- Left Panel - Tabbed Interface -->
            <div class="aisb-editor-panel aisb-editor-panel--left" id="aisb-left-panel">
                <!-- Tab Navigation -->
                <div class="aisb-panel-tabs">
                    <button class="aisb-panel-tab active" data-panel="sections" id="aisb-tab-sections">
                        <span class="dashicons dashicons-layout"></span>
                        <span class="aisb-tab-label"><?php _e('Sections', 'ai-section-builder'); ?></span>
                    </button>
                    <button class="aisb-panel-tab" data-panel="settings" id="aisb-tab-settings">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span class="aisb-tab-label"><?php _e('Settings', 'ai-section-builder'); ?></span>
                    </button>
                </div>
                
                <!-- Sections Panel (Library + Edit modes) -->
                <div id="aisb-panel-sections" class="aisb-panel-content active">
                    <!-- Library Mode -->
                    <div id="aisb-library-mode" class="aisb-panel-mode">
                        <div class="aisb-editor-panel__header">
                            <h2><?php _e('Add Section', 'ai-section-builder'); ?></h2>
                        </div>
                        <div class="aisb-editor-panel__content">
                            <button class="aisb-section-type" data-type="hero">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-megaphone"></span>
                                </div>
                                <div class="aisb-section-type__label">Hero Section</div>
                                <div class="aisb-section-type__description">Eye-catching opener with headline and CTA</div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="aisb-edit-mode" class="aisb-panel-mode" style="display: none;">
                        <div class="aisb-editor-panel__header">
                            <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-back-to-library">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                                <?php _e('Back to Library', 'ai-section-builder'); ?>
                            </button>
                        </div>
                        <div class="aisb-editor-panel__content" id="aisb-edit-content">
                            <!-- Section edit form will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Settings Panel -->
                <div id="aisb-panel-settings" class="aisb-panel-content" style="display: none;">
                    <div class="aisb-editor-panel__header">
                        <h2><?php _e('Global Settings', 'ai-section-builder'); ?></h2>
                    </div>
                    <div class="aisb-editor-panel__content aisb-settings-content">
                        <?php 
                        // Get saved colors
                        $color_settings = \AISB\Settings\Color_Settings::get_instance();
                        $primary_color = $color_settings->get_primary_color();
                        $text_light_color = $color_settings->get_text_light_color();
                        $text_dark_color = $color_settings->get_text_dark_color();
                        ?>
                        
                        <!-- Light Mode Colors -->
                        <div class="aisb-settings-group">
                            <h4 class="aisb-settings-group__title"><?php _e('Light Mode Colors', 'ai-section-builder'); ?></h4>
                            
                            <!-- Primary Color -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-primary"><?php _e('Primary Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-primary" name="colors[primary]" value="<?php echo esc_attr($primary_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($primary_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Used for buttons, links, and interactive elements', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Text Color (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-text-light"><?php _e('Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-text-light" name="colors[text_light]" value="<?php echo esc_attr($text_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($text_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main text color for light sections', 'ai-section-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Dark Mode Colors -->
                        <div class="aisb-settings-group">
                            <h4 class="aisb-settings-group__title"><?php _e('Dark Mode Colors', 'ai-section-builder'); ?></h4>
                            
                            <!-- Text Color (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-text-dark"><?php _e('Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-text-dark" name="colors[text_dark]" value="<?php echo esc_attr($text_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($text_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main text color for dark sections', 'ai-section-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Settings Actions -->
                        <div class="aisb-settings-actions">
                            <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-reset-global-settings">
                                <span class="dashicons dashicons-image-rotate"></span>
                                <?php _e('Reset to Default', 'ai-section-builder'); ?>
                            </button>
                            <button class="aisb-editor-btn aisb-editor-btn-primary" id="aisb-save-global-settings">
                                <span class="dashicons dashicons-saved"></span>
                                <?php _e('Save Color', 'ai-section-builder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Center - Canvas/Preview -->
            <div class="aisb-editor-canvas">
                <div class="aisb-editor-canvas__inner">
                    <div class="aisb-editor-sections" id="aisb-sections-preview">
                        <?php if (empty($sections)): ?>
                            <div class="aisb-editor-empty-state">
                                <span class="dashicons dashicons-layout"></span>
                                <h2><?php _e('Start Building Your Page', 'ai-section-builder'); ?></h2>
                                <p><?php _e('Click a section type to add it to your page', 'ai-section-builder'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel - Page Structure -->
            <div class="aisb-editor-panel aisb-editor-panel--right" id="aisb-structure-panel">
                <div class="aisb-editor-panel__header">
                    <h2><?php _e('Page Structure', 'ai-section-builder'); ?></h2>
                </div>
                <div class="aisb-editor-panel__content" id="aisb-structure-content">
                    <!-- Empty state outside of sortable container -->
                    <div class="aisb-structure-empty" style="display: none;">
                        <span class="dashicons dashicons-editor-ul" aria-hidden="true"></span>
                        <p><?php _e('No sections added yet', 'ai-section-builder'); ?></p>
                    </div>
                    <!-- Sortable container for sections only -->
                    <div id="aisb-section-list" role="list" aria-label="Page sections">
                        <!-- Section items will be rendered here by JavaScript -->
                    </div>
                    
                    <!-- Screen reader instructions -->
                    <div id="aisb-reorder-instructions" class="screen-reader-text">
                        <?php _e('Use arrow keys to navigate sections. Press Enter to select, then use arrow keys to move sections up or down. Press Enter again to confirm position.', 'ai-section-builder'); ?>
                    </div>
                </div>
            </div>
        </div>
        
                </div>
            </div>
        </div>
        
        <!-- Hidden data -->
        <input type="hidden" id="aisb-post-id" value="<?php echo $post_id; ?>" />
        <input type="hidden" id="aisb-existing-sections" value="<?php echo esc_attr(json_encode($sections)); ?>" />
        <?php wp_nonce_field('aisb_editor_nonce', 'aisb_editor_nonce'); ?>
    </div>
    <?php
}

/**
 * Add meta boxes to posts and pages
 */
function aisb_add_meta_boxes() {
    $post_types = ['post', 'page'];
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'aisb_sections',
            __('AI Section Builder', 'ai-section-builder'),
            'aisb_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}

/**
 * Meta box content - Conflict-aware page builder activation
 */
function aisb_meta_box_callback($post) {
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
 */
function aisb_save_meta_box($post_id) {
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
            aisb_backup_original_content($post_id);
            
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
            aisb_backup_original_content($post_id);
            $switched_from = aisb_deactivate_other_builders($post_id);
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
function aisb_backup_original_content($post_id) {
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
function aisb_deactivate_other_builders($post_id) {
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

/**
 * AJAX handler for activating the builder
 */
function aisb_ajax_activate_builder() {
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
 * AJAX handler for deactivating the builder
 */
function aisb_ajax_deactivate_builder() {
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
 * AJAX handler for saving sections
 */
function aisb_ajax_save_sections() {
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
 * Override page template when AI Section Builder is active (conflict-aware)
 * Uses priority 999 to execute after other page builders
 */
function aisb_template_override($template) {
    // Only on singular posts/pages
    if (!is_singular(['post', 'page'])) {
        return $template;
    }
    
    $post_id = get_the_ID();
    
    error_log("AISB: Template override check for post $post_id");
    error_log("AISB: _aisb_enabled = " . get_post_meta($post_id, '_aisb_enabled', true));
    error_log("AISB: _aisb_sections = " . print_r(get_post_meta($post_id, '_aisb_sections', true), true));
    
    // Check for builder conflicts
    $conflict_check = aisb_check_conflicts($post_id);
    
    // If there are conflicts, don't override template
    if ($conflict_check['has_conflicts']) {
        error_log("AISB: Conflicts detected, not overriding template");
        // Set flag for admin notice
        set_transient('aisb_conflict_notice_' . $post_id, $conflict_check['conflicting_builders'], 300);
        return $template;
    }
    
    // Only override if AISB is the active builder
    if (!aisb_is_enabled($post_id)) {
        error_log("AISB: AISB not enabled, not overriding template");
        return $template;
    }
    
    error_log("AISB: Overriding template for post $post_id");
    // Load our custom template
    return aisb_get_canvas_template();
}

/**
 * Add body class for styling
 */
function aisb_body_class($classes) {
    if (is_singular(['post', 'page']) && aisb_has_sections()) {
        $classes[] = 'aisb-canvas';
        $classes[] = 'aisb-template-override';
    }
    return $classes;
}

/**
 * Get canvas template - creates a full-width template
 */
function aisb_get_canvas_template() {
    // Use WordPress temp directory for better reliability
    $temp_template = get_temp_dir() . 'aisb-canvas-' . get_the_ID() . '-' . time() . '.php';
    
    // Create template content
    $template_content = aisb_generate_canvas_template();
    
    // Write template file
    file_put_contents($temp_template, $template_content);
    
    // Clean up file after use
    add_action('wp_footer', function() use ($temp_template) {
        if (file_exists($temp_template)) {
            unlink($temp_template);
        }
    }, 999);
    
    return $temp_template;
}

/**
 * Generate canvas template content
 * This preserves theme header/footer but removes content area styling
 */
function aisb_generate_canvas_template() {
    ob_start();
    ?>
<?php
/**
 * AI Section Builder Canvas Template
 * This template is generated dynamically to preserve theme compatibility
 * while providing full-width section rendering
 */

// Get theme header (preserves navigation, styles, etc.)
get_header();

// Get our sections data (optimized with caching)
$post_id = get_the_ID();
$sections = aisb_get_sections($post_id);
?>

<div id="aisb-canvas" class="aisb-canvas">
    <style>
        /* Remove theme content constraints */
        .aisb-canvas .site-main,
        .aisb-canvas .entry-content,
        .aisb-canvas .page-content,
        .aisb-canvas .post-content,
        .aisb-canvas .content-area,
        .aisb-canvas main,
        .aisb-canvas article {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide theme page title */
        .aisb-canvas .entry-title,
        .aisb-canvas .page-title,
        .aisb-canvas h1.entry-title {
            display: none !important;
        }
        
        /* Ensure proper width without overflow */
        .aisb-canvas {
            width: 100%;
            overflow-x: hidden;
        }
    </style>
    
    <?php
    // Phase 2A: Enable section rendering for Hero sections
    if (!empty($sections) && is_array($sections)) {
        foreach ($sections as $section) {
            // Render section based on type
            if ($section['type'] === 'hero') {
                echo aisb_render_hero_section($section);
            }
        }
    } else {
        // Show placeholder if no sections
        ?>
        <div style="padding: 60px 20px; text-align: center; background: #f5f5f5;">
            <h2>AI Section Builder Active</h2>
            <p>Use the visual editor to add sections to this page.</p>
        </div>
        <?php
    }
    ?>
</div>

<?php
// Get theme footer (preserves footer widgets, scripts, etc.)
get_footer();
?>
    <?php
    return ob_get_clean();
}

/**
 * Migrate old field names to new standardized structure
 */
function aisb_migrate_field_names($content) {
    if (!is_array($content)) {
        return array();
    }
    
    // Don't modify the original array - preserve all fields including media
    $migrated = $content;
    
    // Ensure media fields are preserved
    if (!isset($migrated['media_type']) && isset($content['media_type'])) {
        $migrated['media_type'] = $content['media_type'];
    }
    if (!isset($migrated['video_url']) && isset($content['video_url'])) {
        $migrated['video_url'] = $content['video_url'];
    }
    if (!isset($migrated['featured_image']) && isset($content['featured_image'])) {
        $migrated['featured_image'] = $content['featured_image'];
    }
    
    // Migrate field names
    if (isset($content['eyebrow']) && !isset($content['eyebrow_heading'])) {
        $migrated['eyebrow_heading'] = $content['eyebrow'];
        unset($migrated['eyebrow']);
    }
    if (isset($content['headline']) && !isset($content['heading'])) {
        $migrated['heading'] = $content['headline'];
        unset($migrated['headline']);
    }
    if (isset($content['subheadline']) && !isset($content['content'])) {
        // Wrap in paragraph tags if not already
        $text = $content['subheadline'];
        $migrated['content'] = strpos($text, '<p>') !== false ? $text : '<p>' . $text . '</p>';
        unset($migrated['subheadline']);
    }
    
    // Migrate buttons to global_blocks
    if (isset($content['buttons']) && !isset($content['global_blocks'])) {
        $migrated['global_blocks'] = array_map(function($btn) {
            $btn['type'] = 'button';
            return $btn;
        }, $content['buttons']);
        unset($migrated['buttons']);
    }
    
    // Migrate old single button fields
    if (!empty($content['button_text']) && empty($migrated['global_blocks'])) {
        $migrated['global_blocks'] = array(
            array(
                'type' => 'button',
                'id' => 'btn_migrated_1',
                'text' => $content['button_text'],
                'url' => $content['button_url'] ?? '#',
                'style' => 'primary'
            )
        );
    }
    
    // Migrate media fields to featured_image
    if (isset($content['media_type']) && $content['media_type'] === 'image' && !empty($content['media_image_url'])) {
        $migrated['featured_image'] = $content['media_image_url'];
    }
    
    // Add default variants if not present
    if (!isset($migrated['theme_variant'])) {
        $migrated['theme_variant'] = 'dark';
    }
    if (!isset($migrated['layout_variant'])) {
        $migrated['layout_variant'] = 'content-left';
    }
    
    // Clean up old fields - but NOT our current media fields!
    // DO NOT unset media_type or video_url - we use those!
    unset($migrated['media_image_id']);
    unset($migrated['media_image_url']);
    unset($migrated['media_image_alt']);
    unset($migrated['media_video_type']);
    unset($migrated['button_text']);
    unset($migrated['button_url']);
    
    return $migrated;
}

/**
 * Render Hero section - Standardized field structure
 */
function aisb_render_hero_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Migrate old field names to new structure
    $content = aisb_migrate_field_names($content);
    
    // Debug: Log what we're getting (always log for troubleshooting)
    error_log('AISB Hero Section Content: ' . print_r($content, true));
    
    // Extract standardized fields
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? '');
    $content_text = wp_kses_post($content['content'] ?? '');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Debug: Log extracted media fields
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("AISB Media - Type: $media_type, Image: $featured_image, Video: $video_url");
    }
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'dark');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-hero',
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-hero__container">
            <div class="aisb-hero__grid">
                <div class="aisb-hero__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-hero__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h1 class="aisb-hero__heading"><?php echo $heading; ?></h1>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-hero__body"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    // Render global blocks (buttons for now)
                    $buttons = array_filter($global_blocks, function($block) {
                        return isset($block['type']) && $block['type'] === 'button';
                    });
                    
                    if (!empty($buttons)): ?>
                        <div class="aisb-hero__buttons">
                            <?php foreach ($buttons as $button): ?>
                                <?php if (!empty($button['text'])): ?>
                                    <?php 
                                    $btn_text = esc_html($button['text']);
                                    $btn_url = esc_url($button['url'] ?? '#');
                                    $btn_style = esc_attr($button['style'] ?? 'primary');
                                    $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                                    $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                                    ?>
                                    <a href="<?php echo $btn_url; ?>" 
                                       class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                                       target="<?php echo esc_attr($btn_target); ?>"
                                       <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                        <?php echo $btn_text; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($outro_content): ?>
                        <div class="aisb-hero__outro"><?php echo $outro_content; ?></div>
                    <?php endif; ?>
                </div>
                <?php 
                // Render media based on type
                if ($media_type === 'image' && $featured_image): ?>
                    <div class="aisb-hero__media">
                        <img src="<?php echo $featured_image; ?>" 
                             alt="<?php echo esc_attr($heading); ?>" 
                             class="aisb-hero__image">
                    </div>
                <?php elseif ($media_type === 'video' && $video_url): ?>
                    <div class="aisb-hero__media">
                        <?php 
                        // Check if it's a YouTube URL
                        $is_youtube = preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                        
                        if ($is_youtube && isset($matches[1])): 
                            $youtube_id = $matches[1];
                        ?>
                            <iframe class="aisb-hero__video" 
                                    src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        <?php else: ?>
                            <video class="aisb-hero__video" controls>
                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue frontend styles
 */
function aisb_enqueue_styles() {
    // Only enqueue if we have active sections
    if (!aisb_has_sections()) {
        return;
    }
    
    // Enqueue core design tokens first
    wp_enqueue_style(
        'aisb-tokens',
        AISB_PLUGIN_URL . 'assets/css/core/00-tokens.css',
        array(),
        AISB_VERSION
    );
    
    // Enqueue base architecture - CRITICAL for theme inheritance
    wp_enqueue_style(
        'aisb-base',
        AISB_PLUGIN_URL . 'assets/css/core/01-base.css',
        array('aisb-tokens'),
        AISB_VERSION
    );
    
    // Enqueue utility classes
    wp_enqueue_style(
        'aisb-utilities',
        AISB_PLUGIN_URL . 'assets/css/core/02-utilities.css',
        array('aisb-base'), // Now depends on base, not just tokens
        AISB_VERSION
    );
    
    // For now, load hero section styles (will be dynamic in future)
    wp_enqueue_style(
        'aisb-section-hero',
        AISB_PLUGIN_URL . 'assets/css/sections/hero.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // NO INLINE CSS NEEDED - The CSS architecture handles everything via context variables
    // Removed hardcoded color overrides that were fighting with the CSS variable system
}

/**
 * Enqueue admin styles
 */
function aisb_enqueue_admin_styles($hook) {
    // Load on our admin pages
    if (strpos($hook, 'ai-section-builder') !== false) {
        wp_enqueue_style(
            'aisb-admin-styles',
            AISB_PLUGIN_URL . 'assets/css/admin/admin-styles.css',
            ['wp-admin'],
            AISB_VERSION
        );
    }
    
    // Load editor styles on editor page
    if ($hook === 'admin_page_aisb-editor') {
        // Load EXACT SAME core architecture as frontend for consistency
        wp_enqueue_style(
            'aisb-tokens',
            AISB_PLUGIN_URL . 'assets/css/core/00-tokens.css',
            [],
            AISB_VERSION
        );
        
        // CRITICAL: Add base architecture (was missing!)
        wp_enqueue_style(
            'aisb-base',
            AISB_PLUGIN_URL . 'assets/css/core/01-base.css',
            ['aisb-tokens'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-utilities',
            AISB_PLUGIN_URL . 'assets/css/core/02-utilities.css',
            ['aisb-base'], // Now depends on base, matching frontend
            AISB_VERSION
        );
        
        // Load section styles (same as frontend for consistency)
        wp_enqueue_style(
            'aisb-section-hero',
            AISB_PLUGIN_URL . 'assets/css/sections/hero.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        // Then load editor UI styles (toolbar, panels, etc. - NOT section styles)
        wp_enqueue_style(
            'aisb-editor-styles',
            AISB_PLUGIN_URL . 'assets/css/editor/editor-styles.css',
            ['aisb-section-hero'], // Depends on section styles
            AISB_VERSION
        );
        // Enqueue Sortable.js from CDN with local fallback
        wp_enqueue_script(
            'sortablejs-cdn',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );
        
        // Add local fallback for Sortable.js
        wp_scripts()->add_inline_script(
            'sortablejs-cdn',
            'window.Sortable || document.write("<script src=\"' . 
            AISB_PLUGIN_URL . 'assets/js/vendor/sortable.min.js\">\\x3C/script>");',
            'after'
        );
        
        // Enqueue WordPress media scripts for image/video selection
        wp_enqueue_media();
        
        // Enqueue WordPress editor scripts for TinyMCE/WYSIWYG
        wp_enqueue_editor();
        
        // Enqueue jQuery UI Autocomplete (built into WordPress)
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-autocomplete');
        
        // Enqueue repeater field module
        wp_enqueue_script(
            'aisb-repeater-field',
            AISB_PLUGIN_URL . 'assets/js/editor/repeater-field.js',
            ['jquery', 'sortablejs-cdn'],
            AISB_VERSION,
            true
        );
        
        wp_enqueue_script(
            'aisb-editor-script',
            AISB_PLUGIN_URL . 'assets/js/editor/editor.js',
            ['jquery', 'sortablejs-cdn', 'aisb-repeater-field'],
            AISB_VERSION,
            true
        );
        
        // Enqueue color settings JavaScript
        wp_enqueue_script(
            'aisb-color-settings',
            AISB_PLUGIN_URL . 'assets/js/admin/color-settings.js',
            ['jquery', 'aisb-editor-script'],
            AISB_VERSION,
            true
        );
        
        // Localize color settings script with nonce
        wp_localize_script('aisb-color-settings', 'aisbColorSettings', [
            'nonce' => wp_create_nonce('aisb_color_settings')
        ]);
        
        // Localize script with settings for drag-drop functionality
        wp_localize_script('aisb-editor-script', 'aisbEditor', array(
            'nonce' => wp_create_nonce('aisb_editor_nonce'),
            'aisbNonce' => wp_create_nonce('aisb_nonce'), // For global settings AJAX
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('aisb/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'features' => array(
                'dragDrop' => true, // Now enabled with Sortable.js
                'autoSave' => true,
                'keyboardNav' => true
            ),
            'settings' => array(
                'autoSaveDelay' => 2000,
                'maxRetries' => 3,
                'timeoutDuration' => 30000
            ),
            'i18n' => array(
                'reorderMode' => __('Reorder mode activated. Use arrow keys to move section, Enter to confirm, Escape to cancel.', 'ai-section-builder'),
                'sectionMoved' => __('Section moved successfully', 'ai-section-builder'),
                'reorderCancelled' => __('Reorder cancelled', 'ai-section-builder'),
                'autoSaved' => __('Changes saved automatically', 'ai-section-builder'),
                'saveFailed' => __('Save failed', 'ai-section-builder'),
                'networkError' => __('Network error. Please check your connection.', 'ai-section-builder'),
                'confirmDelete' => __('Are you sure you want to delete this section?', 'ai-section-builder')
            )
        ));
        
        // Add body class for editor
        add_filter('admin_body_class', function($classes) {
            return $classes . ' aisb-editor-active';
        });
    }
    
    // Load on post/page edit screens for meta box
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        $screen = get_current_screen();
        if ($screen && in_array($screen->post_type, ['post', 'page'])) {
            wp_enqueue_style(
                'aisb-admin-styles',
                AISB_PLUGIN_URL . 'assets/css/admin/admin-styles.css',
                ['wp-admin'],
                AISB_VERSION
            );
        }
    }
}

/**
 * Detect active page builders on a post
 * 
 * @param int $post_id Post ID to check
 * @return array Array of active builder slugs
 */
function aisb_detect_active_builders($post_id) {
    $builders = [];
    
    // Elementor
    if (get_post_meta($post_id, '_elementor_data', true)) {
        $builders[] = 'elementor';
    }
    
    // Beaver Builder
    if (get_post_meta($post_id, '_fl_builder_data', true)) {
        $builders[] = 'beaver-builder';
    }
    
    // Divi
    if (get_post_meta($post_id, '_et_pb_use_builder', true) === 'on') {
        $builders[] = 'divi';
    }
    
    // WPBakery (Visual Composer)
    if (get_post_meta($post_id, '_wpb_vc_js_status', true)) {
        $builders[] = 'wpbakery';
    }
    
    // Breakdance
    if (get_post_meta($post_id, '_breakdance_data', true)) {
        $builders[] = 'breakdance';
    }
    
    // Bricks
    if (get_post_meta($post_id, '_bricks_page_content_2', true)) {
        $builders[] = 'bricks';
    }
    
    // Our plugin
    if (aisb_is_enabled($post_id)) {
        $builders[] = 'aisb';
    }
    
    return $builders;
}

/**
 * Get friendly name for page builder
 * 
 * @param string $builder Builder slug
 * @return string Friendly name
 */
function aisb_get_builder_name($builder) {
    $names = [
        'elementor' => 'Elementor',
        'beaver-builder' => 'Beaver Builder',
        'divi' => 'Divi Builder',
        'wpbakery' => 'WPBakery Page Builder',
        'breakdance' => 'Breakdance',
        'bricks' => 'Bricks Builder',
        'aisb' => 'AI Section Builder'
    ];
    
    return $names[$builder] ?? ucfirst($builder);
}

/**
 * Check if AI Section Builder is enabled for a post (optimized with caching)
 * 
 * @param int $post_id Post ID
 * @return bool
 */
function aisb_is_enabled($post_id) {
    $cache_key = 'aisb_enabled_' . $post_id;
    
    // Check cache first
    $cached = wp_cache_get($cache_key, 'aisb');
    if ($cached !== false) {
        return (bool) $cached;
    }
    
    // Check explicit enable flag ONLY
    // Phase 1B: Don't check sections since we don't create them yet
    $enabled = get_post_meta($post_id, '_aisb_enabled', true);
    
    // Must be explicitly enabled (1 or '1')
    $result = ($enabled == 1);
    
    // Cache for 1 hour
    wp_cache_set($cache_key, $result ? 1 : 0, 'aisb', HOUR_IN_SECONDS);
    
    return $result;
}

/**
 * Check if current page has sections
 */
function aisb_has_sections() {
    if (!is_singular(['post', 'page'])) {
        return false;
    }
    
    return aisb_is_enabled(get_the_ID());
}

/**
 * Check if there are builder conflicts on a post
 * 
 * @param int $post_id Post ID
 * @return array Array with conflict info
 */
function aisb_check_conflicts($post_id) {
    $active_builders = aisb_detect_active_builders($post_id);
    $conflicts = array_diff($active_builders, ['aisb']);
    
    return [
        'has_conflicts' => !empty($conflicts),
        'conflicting_builders' => $conflicts,
        'all_builders' => $active_builders
    ];
}

/**
 * Display admin notices for conflicts and other issues
 */
function aisb_admin_notices() {
    $screen = get_current_screen();
    
    // Only show on post edit screens
    if (!$screen || !in_array($screen->id, ['post', 'page'])) {
        return;
    }
    
    // Check for notices
    if (isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        
        // Check for conflict notices
        $conflicts = get_transient('aisb_conflict_notice_' . $post_id);
        if ($conflicts) {
            $builder_names = array_map('aisb_get_builder_name', $conflicts);
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Template override disabled due to conflicts with:', 'ai-section-builder'); ?>
                    <strong><?php echo esc_html(implode(', ', $builder_names)); ?></strong>
                </p>
                <p>
                    <a href="#aisb-meta-box" class="button button-secondary">
                        <?php _e('Resolve Conflict', 'ai-section-builder'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('aisb_conflict_notice_' . $post_id);
        }
        
        // Check for deactivation success notice
        if (get_transient('aisb_deactivated_' . $post_id)) {
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Page builder has been deactivated. Your sections have been preserved and will be restored if you reactivate.', 'ai-section-builder'); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_deactivated_' . $post_id);
        }
        
        // Check for activation success notice
        if (get_transient('aisb_activated_' . $post_id)) {
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Successfully activated! You can now edit with the AI Section Builder.', 'ai-section-builder'); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_activated_' . $post_id);
        }
        
        // Check for switch success notice
        $switched_from = get_transient('aisb_switched_' . $post_id);
        if ($switched_from && !empty($switched_from)) {
            $builder_names = array_map('aisb_get_builder_name', $switched_from);
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php printf(
                        _n(
                            'Successfully switched from %s. Your original content has been preserved.',
                            'Successfully switched from %s. Your original content has been preserved.',
                            count($builder_names),
                            'ai-section-builder'
                        ),
                        '<strong>' . implode(', ', $builder_names) . '</strong>'
                    ); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_switched_' . $post_id);
        }
    }
}

/**
 * Debug function - call this to see current state of a post
 * Usage: Add ?aisb_debug=1 to any post URL to see debug info
 */
function aisb_debug_post_state() {
    // Only for logged in admins with debug query param
    if (!current_user_can('manage_options') || !isset($_GET['aisb_debug'])) {
        return;
    }
    
    if (!is_singular(['post', 'page'])) {
        return;
    }
    
    $post_id = get_the_ID();
    
    echo '<div style="position: fixed; top: 0; right: 0; background: #000; color: #0f0; padding: 20px; font-family: monospace; font-size: 12px; z-index: 99999; max-width: 400px; overflow: auto; max-height: 100vh;">';
    echo '<h3 style="color: #fff; margin-top: 0;">AISB Debug - Post ' . $post_id . '</h3>';
    
    echo '<strong>Meta Data:</strong><br>';
    echo '_aisb_enabled: ' . var_export(get_post_meta($post_id, '_aisb_enabled', true), true) . '<br>';
    echo '_aisb_sections: ' . htmlspecialchars(print_r(get_post_meta($post_id, '_aisb_sections', true), true)) . '<br>';
    
    echo '<strong>Builder Detection:</strong><br>';
    $active_builders = aisb_detect_active_builders($post_id);
    echo 'Active builders: ' . implode(', ', $active_builders) . '<br>';
    
    $conflict_check = aisb_check_conflicts($post_id);
    echo 'Has conflicts: ' . var_export($conflict_check['has_conflicts'], true) . '<br>';
    echo 'Conflicting builders: ' . implode(', ', $conflict_check['conflicting_builders']) . '<br>';
    
    echo '<strong>Function Results:</strong><br>';
    echo 'aisb_is_enabled(): ' . var_export(aisb_is_enabled($post_id), true) . '<br>';
    echo 'aisb_has_sections(): ' . var_export(aisb_has_sections(), true) . '<br>';
    
    echo '<strong>Template Override:</strong><br>';
    echo 'is_singular: ' . var_export(is_singular(['post', 'page']), true) . '<br>';
    echo 'body_class: ' . implode(' ', get_body_class()) . '<br>';
    
    echo '<strong>Cache Check:</strong><br>';
    echo 'sections cache: ' . htmlspecialchars(print_r(wp_cache_get('aisb_sections_' . $post_id, 'aisb'), true)) . '<br>';
    echo 'enabled cache: ' . htmlspecialchars(print_r(wp_cache_get('aisb_enabled_' . $post_id, 'aisb'), true)) . '<br>';
    
    echo '</div>';
}
add_action('wp_footer', 'aisb_debug_post_state');

/**
 * Migration: Clean up old Phase 1A hero data
 * This runs once to clean up test data from Phase 1A development
 */
function aisb_migrate_cleanup_old_data() {
    // Check if migration has already run
    $migration_version = get_option('aisb_migration_version', 0);
    
    if ($migration_version < 1) {
        global $wpdb;
        
        // Find all posts with old hero section data
        $posts_with_sections = $wpdb->get_col(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_aisb_sections'"
        );
        
        foreach ($posts_with_sections as $post_id) {
            $sections = get_post_meta($post_id, '_aisb_sections', true);
            
            // Check if this contains old Phase 1A hero data
            if (is_array($sections)) {
                $has_old_hero = false;
                foreach ($sections as $section) {
                    if (isset($section['type']) && $section['type'] === 'hero' && 
                        (isset($section['headline']) || isset($section['subheadline']))) {
                        $has_old_hero = true;
                        break;
                    }
                }
                
                // If old hero data found, clear it
                if ($has_old_hero) {
                    delete_post_meta($post_id, '_aisb_sections');
                    
                    // Also clear enabled flag if no editor is ready yet
                    // Phase 1B doesn't create sections, so having enabled without sections is invalid
                    update_post_meta($post_id, '_aisb_enabled', 0);
                    
                    // Clear cache
                    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
                    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
                    
                    error_log("AISB Migration: Cleaned old hero data from post $post_id");
                }
            }
        }
        
        // Mark migration as complete
        update_option('aisb_migration_version', 1);
        error_log("AISB Migration: Phase 1A cleanup complete");
    }
}

/**
 * Database performance optimizations
 */

/**
 * Add database indexes for better performance
 */
function aisb_add_database_indexes() {
    global $wpdb;
    
    // Add indexes for our meta keys to improve query performance
    $indexes = [
        "_aisb_enabled",
        "_aisb_sections", 
        "_aisb_original_content",
        "_aisb_switched_from"
    ];
    
    foreach ($indexes as $meta_key) {
        $wpdb->query($wpdb->prepare(
            "ALTER TABLE {$wpdb->postmeta} 
             ADD INDEX IF NOT EXISTS aisb_{$meta_key} (meta_key(20), meta_value(10))",
            $meta_key
        ));
    }
}

/**
 * Optimized function to get sections with caching
 * 
 * @param int $post_id Post ID
 * @param bool $use_cache Whether to use cache
 * @return array|false Sections data or false
 */
function aisb_get_sections($post_id, $use_cache = true) {
    $cache_key = 'aisb_sections_' . $post_id;
    
    if ($use_cache) {
        $cached = wp_cache_get($cache_key, 'aisb');
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $sections = get_post_meta($post_id, '_aisb_sections', true);
    
    // Validate and sanitize sections data
    if (!is_array($sections)) {
        $sections = [];
    }
    
    // Cache for 1 hour
    if ($use_cache) {
        wp_cache_set($cache_key, $sections, 'aisb', HOUR_IN_SECONDS);
    }
    
    return $sections;
}

/**
 * Optimized function to update sections with cache invalidation
 * 
 * @param int $post_id Post ID
 * @param array $sections Sections data
 * @return bool Success
 */
function aisb_update_sections($post_id, $sections) {
    // Validate sections data
    if (!is_array($sections)) {
        return false;
    }
    
    // Sanitize sections data
    $sections = aisb_sanitize_sections($sections);
    
    // Update post meta
    $result = update_post_meta($post_id, '_aisb_sections', $sections);
    
    // Invalidate cache
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
    
    return $result !== false;
}

/**
 * Sanitize sections data for security and performance
 * 
 * @param array $sections Raw sections data
 * @return array Sanitized sections data
 */
function aisb_sanitize_sections($sections) {
    if (!is_array($sections)) {
        return [];
    }
    
    $sanitized = [];
    
    foreach ($sections as $section) {
        if (!is_array($section) || !isset($section['type'])) {
            continue;
        }
        
        $clean_section = [
            'type' => sanitize_key($section['type']),
            'id' => isset($section['id']) ? sanitize_text_field($section['id']) : uniqid('section_')
        ];
        
        // Sanitize based on section type
        switch ($section['type']) {
            case 'hero':
                $clean_section['headline'] = sanitize_text_field($section['headline'] ?? '');
                $clean_section['subheadline'] = sanitize_textarea_field($section['subheadline'] ?? '');
                $clean_section['button_text'] = sanitize_text_field($section['button_text'] ?? '');
                $clean_section['button_url'] = esc_url_raw($section['button_url'] ?? '#');
                break;
                
            default:
                // For future section types, apply generic sanitization
                foreach ($section as $key => $value) {
                    if ($key !== 'type' && $key !== 'id') {
                        if (is_string($value)) {
                            $clean_section[$key] = sanitize_text_field($value);
                        } elseif (is_array($value)) {
                            $clean_section[$key] = array_map('sanitize_text_field', $value);
                        }
                    }
                }
                break;
        }
        
        $sanitized[] = $clean_section;
    }
    
    return $sanitized;
}

/**
 * Batch query optimization for multiple posts
 * 
 * @param array $post_ids Array of post IDs
 * @return array Associative array of post_id => sections
 */
function aisb_get_multiple_post_sections($post_ids) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return [];
    }
    
    global $wpdb;
    
    // Prepare placeholders for IN query
    $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    
    // Single query to get all sections at once
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_value 
         FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_sections' 
         AND post_id IN ($placeholders)",
        $post_ids
    ));
    
    $sections_data = [];
    
    foreach ($results as $row) {
        $sections = maybe_unserialize($row->meta_value);
        $sections_data[$row->post_id] = is_array($sections) ? $sections : [];
    }
    
    // Fill in empty arrays for posts without sections
    foreach ($post_ids as $post_id) {
        if (!isset($sections_data[$post_id])) {
            $sections_data[$post_id] = [];
        }
    }
    
    return $sections_data;
}

/**
 * Register REST API routes for link selection
 */
function aisb_register_rest_routes() {
    register_rest_route('aisb/v1', '/search-content', array(
        'methods' => 'GET',
        'callback' => 'aisb_search_content_callback',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'search' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'per_page' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 20,
            ),
        ),
    ));
}

/**
 * REST API callback for searching pages and posts
 */
function aisb_search_content_callback($request) {
    $search_term = $request->get_param('search');
    $per_page = $request->get_param('per_page');
    
    // Build query args
    $args = array(
        'post_type' => array('page', 'post'),
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'orderby' => 'relevance',
        'order' => 'DESC',
    );
    
    if (!empty($search_term)) {
        $args['s'] = $search_term;
    }
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_type_obj = get_post_type_object(get_post_type());
            
            $results[] = array(
                'id' => get_the_ID(),
                'text' => get_the_title() . ' (' . $post_type_obj->labels->singular_name . ')',
                'url' => get_permalink(),
                'type' => get_post_type(),
            );
        }
        wp_reset_postdata();
    }
    
    // Add option for custom URL at the beginning
    if (empty($search_term) || strpos(strtolower('custom url'), strtolower($search_term)) !== false) {
        array_unshift($results, array(
            'id' => 'custom',
            'text' => '— Enter Custom URL —',
            'url' => '',
            'type' => 'custom',
        ));
    }
    
    return rest_ensure_response(array('results' => $results));
}

/**
 * Clear cache when post is saved
 * 
 * @param int $post_id Post ID
 */
function aisb_clear_cache_on_save($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Clear section and enabled caches for this post
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
}

/**
 * Clear cache when post is deleted
 * 
 * @param int $post_id Post ID
 */
function aisb_clear_cache_on_delete($post_id) {
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
}

/**
 * Get database performance statistics (for debugging/monitoring)
 * 
 * @return array Performance stats
 */
function aisb_get_performance_stats() {
    global $wpdb;
    
    // Get counts of posts using AISB
    $enabled_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_enabled' AND meta_value = '1'"
    );
    
    $sections_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_sections'"
    );
    
    // Check if indexes exist
    $indexes = $wpdb->get_results(
        "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name LIKE 'aisb_%'"
    );
    
    return [
        'enabled_posts' => (int) $enabled_count,
        'posts_with_sections' => (int) $sections_count,
        'database_indexes' => count($indexes),
        'cache_group' => 'aisb',
        'optimization_level' => 'advanced'
    ];
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'aisb_activate');

function aisb_activate() {
    // Add database indexes for performance
    aisb_add_database_indexes();
    
    // Set activation timestamp
    update_option('aisb_activated', current_time('timestamp'));
    
    // Future: Create database tables if needed for advanced features
}

/**
 * Deactivation hook  
 */
register_deactivation_hook(__FILE__, 'aisb_deactivate');

function aisb_deactivate() {
    // Clean up if needed
}
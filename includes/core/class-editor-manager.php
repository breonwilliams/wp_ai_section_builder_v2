<?php
/**
 * Editor Manager Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages the visual editor interface
 */
class Editor_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Visual editor page
        add_action('admin_menu', array($this, 'add_editor_page'));
        add_action('admin_init', array($this, 'handle_editor_redirect'));
    }
    
    /**
     * Add hidden editor page
     */
    public function add_editor_page() {
        add_submenu_page(
            null, // No parent menu - hidden page
            __('AI Section Builder Editor', 'ai-section-builder'),
            __('Editor', 'ai-section-builder'),
            'edit_posts',
            'aisb-editor',
            array($this, 'render_editor_page')
        );
    }
    
    /**
     * Handle redirect to editor when clicking "Build with AI Section Builder"
     */
    public function handle_editor_redirect() {
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
    public function render_editor_page() {
        // Get post ID from URL
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (!$post_id) {
            $this->render_no_post_selected();
            return;
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
        
        // Load the editor template
        require_once AISB_PLUGIN_DIR . 'templates/editor-page.php';
    }
    
    /**
     * Render the no post selected message
     */
    private function render_no_post_selected() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Section Builder - Visual Editor', 'ai-section-builder'); ?></h1>
            <div style="background: #fff3cd; border: 1px solid #dba617; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><span class="dashicons dashicons-warning" style="color: #dba617;"></span> <?php _e('No Post Selected', 'ai-section-builder'); ?></h2>
                <p><?php _e('The Section Editor requires a specific page or post to edit. To use the visual editor:', 'ai-section-builder'); ?></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Go to any page or post in WordPress', 'ai-section-builder'); ?></li>
                    <li><?php _e('Look for the "AI Section Builder" meta box in the editor sidebar', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Build with AI Section Builder" to activate the plugin for that page', 'ai-section-builder'); ?></li>
                    <li><?php _e('Then click "Edit with AI Section Builder" to open the visual editor', 'ai-section-builder'); ?></li>
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
        </div>
        <?php
    }
}
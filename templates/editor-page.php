<?php
/**
 * Editor Page Template
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// These variables are set by the Editor_Manager class:
// $post_id, $post, $sections

// Debug information
if (defined('WP_DEBUG') && WP_DEBUG) {
    $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
    echo "<!-- AISB Editor Debug Info -->\n";
    echo "<!-- Plugin URL: " . esc_html($plugin_url) . " -->\n";
    echo "<!-- Plugin Dir: " . esc_html(AISB_PLUGIN_DIR) . " -->\n";
    echo "<!-- Editor JS URL: " . esc_html($plugin_url . 'assets/js/editor/editor.js') . " -->\n";
    echo "<!-- Editor JS exists: " . (file_exists(AISB_PLUGIN_DIR . 'assets/js/editor/editor.js') ? 'YES' : 'NO') . " -->\n";
    echo "<!-- URL Temp Flag: " . (defined('AISB_PLUGIN_URL_TEMP') ? 'YES' : 'NO') . " -->\n";
    echo "<!-- URL Corrected: " . (defined('AISB_PLUGIN_URL_CORRECTED') ? AISB_PLUGIN_URL_CORRECTED : 'NO') . " -->\n";
    echo "<!-- End AISB Debug -->\n";
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
            <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-upload-document">
                <span class="dashicons dashicons-media-text"></span>
                <?php _e('Upload Document', 'ai-section-builder'); ?>
            </button>
            <button class="aisb-editor-btn aisb-editor-btn-danger" id="aisb-clear-all-sections" title="<?php _e('Remove all sections from this page', 'ai-section-builder'); ?>">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear All', 'ai-section-builder'); ?>
            </button>
            <button class="aisb-editor-btn aisb-editor-btn-primary" id="aisb-save-sections">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save', 'ai-section-builder'); ?>
            </button>
            <!-- Hidden file input for document upload -->
            <input type="file" id="aisb-document-file" accept=".docx,.doc,.txt" style="display: none;" data-post-id="<?php echo esc_attr($post_id); ?>" />
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
                        <?php
                        $section_types = [
                            'hero' => ['icon' => 'megaphone', 'label' => 'Hero Section', 'description' => 'Eye-catching opener with headline and CTA'],
                            'hero-form' => ['icon' => 'feedback', 'label' => 'Hero with Form', 'description' => 'Hero section with form area'],
                            'features' => ['icon' => 'screenoptions', 'label' => 'Features Section', 'description' => 'Showcase key features with icons and descriptions'],
                            'checklist' => ['icon' => 'yes-alt', 'label' => 'Checklist Section', 'description' => 'List benefits or features with checkmarks'],
                            'faq' => ['icon' => 'editor-help', 'label' => 'FAQ Section', 'description' => 'Answer frequently asked questions'],
                            'stats' => ['icon' => 'chart-bar', 'label' => 'Stats Section', 'description' => 'Display key metrics and numbers'],
                            'testimonials' => ['icon' => 'format-quote', 'label' => 'Testimonials Section', 'description' => 'Showcase customer reviews and testimonials']
                        ];
                        
                        foreach ($section_types as $type => $info): ?>
                            <button class="aisb-section-type" data-type="<?php echo esc_attr($type); ?>">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($info['icon']); ?>"></span>
                                </div>
                                <div class="aisb-section-type__label"><?php echo esc_html($info['label']); ?></div>
                                <div class="aisb-section-type__description"><?php echo esc_html($info['description']); ?></div>
                            </button>
                        <?php endforeach; ?>
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
                    $base_light_color = $color_settings->get_base_light_color();
                    $base_dark_color = $color_settings->get_base_dark_color();
                    $text_light_color = $color_settings->get_text_light_color();
                    $text_dark_color = $color_settings->get_text_dark_color();
                    $secondary_light_color = $color_settings->get_secondary_light_color();
                    $secondary_dark_color = $color_settings->get_secondary_dark_color();
                    $border_light_color = $color_settings->get_border_light_color();
                    $border_dark_color = $color_settings->get_border_dark_color();
                    $muted_light_color = $color_settings->get_muted_light_color();
                    $muted_dark_color = $color_settings->get_muted_dark_color();
                    ?>
                    
                    <!-- Light Mode Colors -->
                    <div class="aisb-settings-group">
                        <h4 class="aisb-settings-group__title"><?php _e('Light Mode Colors', 'ai-section-builder'); ?></h4>
                        
                        <?php
                        $light_colors = [
                            'primary' => ['label' => 'Primary Color', 'help' => 'Used for buttons, links, and interactive elements'],
                            'base_light' => ['label' => 'Background Color', 'help' => 'Main background color for light sections'],
                            'text_light' => ['label' => 'Text Color', 'help' => 'Main text color for light sections'],
                            'muted_light' => ['label' => 'Muted Text Color', 'help' => 'Secondary text color for descriptions and subtle content'],
                            'secondary_light' => ['label' => 'Secondary Background', 'help' => 'Background color for cards and alternate sections'],
                            'border_light' => ['label' => 'Border Color', 'help' => 'Border color for cards and dividers']
                        ];
                        
                        foreach ($light_colors as $key => $info):
                            $var_name = $key . '_color';
                            $value = $$var_name;
                        ?>
                        <div class="aisb-settings-field">
                            <label for="aisb-gs-<?php echo esc_attr($key); ?>"><?php echo esc_html__($info['label'], 'ai-section-builder'); ?></label>
                            <div class="aisb-color-input-wrapper">
                                <input type="color" id="aisb-gs-<?php echo esc_attr($key); ?>" name="colors[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" />
                                <input type="text" class="aisb-color-text" value="<?php echo esc_attr($value); ?>" />
                            </div>
                            <p class="aisb-settings-help"><?php echo esc_html__($info['help'], 'ai-section-builder'); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Dark Mode Colors -->
                    <div class="aisb-settings-group">
                        <h4 class="aisb-settings-group__title"><?php _e('Dark Mode Colors', 'ai-section-builder'); ?></h4>
                        
                        <?php
                        $dark_colors = [
                            'base_dark' => ['label' => 'Background Color', 'help' => 'Main background color for dark sections'],
                            'text_dark' => ['label' => 'Text Color', 'help' => 'Main text color for dark sections'],
                            'muted_dark' => ['label' => 'Muted Text Color', 'help' => 'Secondary text color for descriptions in dark mode'],
                            'secondary_dark' => ['label' => 'Secondary Background', 'help' => 'Background color for cards in dark mode'],
                            'border_dark' => ['label' => 'Border Color', 'help' => 'Border color for cards in dark mode']
                        ];
                        
                        foreach ($dark_colors as $key => $info):
                            $var_name = $key . '_color';
                            $value = $$var_name;
                        ?>
                        <div class="aisb-settings-field">
                            <label for="aisb-gs-<?php echo esc_attr($key); ?>"><?php echo esc_html__($info['label'], 'ai-section-builder'); ?></label>
                            <div class="aisb-color-input-wrapper">
                                <input type="color" id="aisb-gs-<?php echo esc_attr($key); ?>" name="colors[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" />
                                <input type="text" class="aisb-color-text" value="<?php echo esc_attr($value); ?>" />
                            </div>
                            <p class="aisb-settings-help"><?php echo esc_html__($info['help'], 'ai-section-builder'); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Settings Actions -->
                    <div class="aisb-settings-actions" style="display: flex; flex-direction: column; gap: 12px;">
                        <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-reset-global-settings">
                            <span class="dashicons dashicons-image-rotate"></span>
                            <?php _e('Reset to Default', 'ai-section-builder'); ?>
                        </button>
                        <p class="aisb-settings-help" style="text-align: center; margin: 0; font-size: 13px; color: #9CA3AF;">
                            <?php _e('Use the main Save button above to save all changes', 'ai-section-builder'); ?>
                        </p>
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
    
    <!-- Hidden data -->
    <input type="hidden" id="aisb-post-id" value="<?php echo $post_id; ?>" />
    <input type="hidden" id="aisb-existing-sections" value="<?php echo esc_attr(json_encode($sections)); ?>" />
    <?php wp_nonce_field('aisb_editor_nonce', 'aisb_editor_nonce'); ?>
</div>
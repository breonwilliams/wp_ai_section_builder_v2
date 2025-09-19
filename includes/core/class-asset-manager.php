<?php
/**
 * Asset Manager Class
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
 * Manages all CSS and JS asset loading
 */
class Asset_Manager {
    
    /**
     * Section types available
     *
     * @var array
     */
    private $section_types = [
        'hero',
        'hero-form',
        'features',
        'checklist',
        'faq',
        'stats',
        'testimonials'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Enqueue styles with high priority to ensure they load after theme styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        // Only enqueue if we have active sections
        if (!aisb_has_sections()) {
            return;
        }
        
        // Get corrected plugin URL
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        
        // Enqueue core design system
        $this->enqueue_core_styles();
        
        // Enqueue section styles
        $this->enqueue_section_styles();
        
        // Load FAQ accordion JavaScript
        wp_enqueue_script(
            'aisb-faq-accordion',
            $plugin_url . 'assets/js/frontend/faq-accordion-vanilla.js',
            array(), // No dependencies
            AISB_VERSION,
            true
        );
    }
    
    /**
     * Enqueue admin styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_styles($hook) {
        // Get corrected plugin URL
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        
        // Load on our admin pages
        if (strpos($hook, 'ai-section-builder') !== false) {
            wp_enqueue_style(
                'aisb-admin-styles',
                $plugin_url . 'assets/css/admin/admin-styles.css',
                ['wp-admin'],
                AISB_VERSION
            );
        }
        
        // Load editor styles on editor page
        if ($hook === 'admin_page_aisb-editor') {
            $this->enqueue_editor_styles();
        }
    }
    
    /**
     * Enqueue core design system styles
     */
    private function enqueue_core_styles() {
        // Get corrected plugin URL
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        
        // Enqueue core design tokens first
        wp_enqueue_style(
            'aisb-tokens',
            $plugin_url . 'assets/css/core/00-tokens.css',
            array(),
            AISB_VERSION
        );
        
        // Enqueue base architecture
        wp_enqueue_style(
            'aisb-base',
            $plugin_url . 'assets/css/core/01-base.css',
            array('aisb-tokens'),
            AISB_VERSION
        );
        
        // Enqueue utility classes
        wp_enqueue_style(
            'aisb-utilities',
            $plugin_url . 'assets/css/core/02-utilities.css',
            array('aisb-base'),
            AISB_VERSION
        );
    }
    
    /**
     * Enqueue section styles
     */
    private function enqueue_section_styles() {
        // Get corrected plugin URL
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        
        foreach ($this->section_types as $type) {
            $handle = 'aisb-section-' . $type;
            $file = str_replace('-', '-', $type); // Keep hyphenated names
            
            wp_enqueue_style(
                $handle,
                $plugin_url . 'assets/css/sections/' . $file . '.css',
                array('aisb-utilities'),
                AISB_VERSION
            );
        }
    }
    
    /**
     * Enqueue editor-specific styles
     */
    private function enqueue_editor_styles() {
        // Get the correct plugin URL first (before any usage)
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        
        // Load core styles (same as frontend for consistency)
        $this->enqueue_core_styles();
        
        // Load section styles
        $this->enqueue_section_styles();
        
        // Build dependencies array
        $deps = [];
        foreach ($this->section_types as $type) {
            $deps[] = 'aisb-section-' . $type;
        }
        
        // Load editor UI styles
        wp_enqueue_style(
            'aisb-editor-styles',
            $plugin_url . 'assets/css/editor/editor-styles.css',
            $deps,
            AISB_VERSION
        );
        
        // CRITICAL: Enqueue WordPress Editor (TinyMCE) for WYSIWYG functionality
        wp_enqueue_editor();
        
        // Enqueue WordPress Media scripts for image/video uploads
        wp_enqueue_media();
        
        // Enqueue jQuery UI Autocomplete for URL field autocomplete
        wp_enqueue_script('jquery-ui-autocomplete');
        
        // Enqueue Sortable.js
        wp_enqueue_script(
            'sortablejs-cdn',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            false
        );
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Asset_Manager: Using plugin URL: ' . $plugin_url);
            error_log('Asset_Manager: Editor JS path: ' . $plugin_url . 'assets/js/editor/editor.js');
        }
        
        // Local fallback for Sortable (corrected path)
        wp_add_inline_script(
            'sortablejs-cdn',
            'window.Sortable || document.write(\'<script src="' . $plugin_url . 'assets/js/vendor/sortable.min.js"><\/script>\')',
            'after'
        );
        
        // Enqueue repeater field JavaScript (CRITICAL for repeatable blocks)
        wp_enqueue_script(
            'aisb-repeater-field',
            $plugin_url . 'assets/js/editor/repeater-field.js',
            ['jquery', 'sortablejs-cdn'],
            AISB_VERSION,
            true
        );
        
        // Editor main JavaScript (corrected path)
        $editor_js_url = $plugin_url . 'assets/js/editor/editor.js';
        $editor_js_path = AISB_PLUGIN_DIR . 'assets/js/editor/editor.js';
        
        // Check if file exists before enqueuing
        if (file_exists($editor_js_path)) {
            wp_enqueue_script(
                'aisb-editor-script',
                $editor_js_url,
                ['jquery', 'sortablejs-cdn', 'aisb-repeater-field', 'wp-tinymce'],
                AISB_VERSION . '-' . time(),
                true
            );
            
            // Localize editor script - using aisbEditor for consistency with JS
            wp_localize_script('aisb-editor-script', 'aisbEditor', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'ajaxurl' => admin_url('admin-ajax.php'), // Keep both for compatibility
                'nonce' => wp_create_nonce('aisb_editor_nonce'),
                'plugin_url' => $plugin_url,
                // REST API data for autocomplete
                'restUrl' => esc_url_raw(rest_url('wp/v2/')),
                'restNonce' => wp_create_nonce('wp_rest'),
                // Status messages
                'saving' => __('Saving...', 'ai-section-builder'),
                'saved' => __('Changes saved', 'ai-section-builder'),
                'error' => __('Error saving changes', 'ai-section-builder'),
                'confirm_delete' => __('Are you sure you want to delete this section?', 'ai-section-builder'),
                'rendering' => __('Rendering form...', 'ai-section-builder'),
                'render_error' => __('Error rendering form', 'ai-section-builder'),
                // Media library strings
                'media_title' => __('Select or Upload Media', 'ai-section-builder'),
                'media_button' => __('Use this media', 'ai-section-builder'),
                // i18n strings for drag and drop
                'i18n' => [
                    'sectionMoved' => __('Section moved successfully', 'ai-section-builder'),
                    'reorderCancelled' => __('Reorder cancelled', 'ai-section-builder'),
                    'reorderMode' => __('Reorder mode: Use arrow keys to move items', 'ai-section-builder')
                ],
                // Feature flags
                'features' => [
                    'dragDrop' => true
                ]
            ]);
            
            // Also provide aisb_editor_ajax for backward compatibility
            wp_localize_script('aisb-editor-script', 'aisb_editor_ajax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aisb_editor_nonce'),
                'plugin_url' => $plugin_url
            ]);
        } else {
            // File doesn't exist - log error for debugging
            error_log('AISB ERROR: Editor.js not found at: ' . $editor_js_path);
            error_log('AISB ERROR: Expected URL would be: ' . $editor_js_url);
            
            // Add admin notice if in admin area
            if (is_admin()) {
                add_action('admin_notices', function() use ($editor_js_path) {
                    echo '<div class="notice notice-error"><p>';
                    echo 'AI Section Builder: Editor JavaScript file not found at ' . esc_html($editor_js_path);
                    echo '</p></div>';
                });
            }
        }
    }
}
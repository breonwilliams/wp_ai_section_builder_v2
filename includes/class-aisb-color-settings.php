<?php
/**
 * Color Settings Management Class
 * 
 * Handles storage and retrieval of global color settings
 * Starting with primary color only for testing
 * 
 * @package AISB
 * @since 2.0.0
 */

namespace AISB\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Color_Settings {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'aisb_color_settings';
    
    /**
     * Settings version for future migrations
     */
    const VERSION = '1.0.0';
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Default primary color
     */
    private $default_primary = '#2563eb';
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize hooks
     */
    private function init() {
        // Add CSS variables to frontend (priority 100 to load AFTER theme styles)
        add_action('wp_head', [$this, 'output_css_variables'], 100);
        
        // Add CSS variables to admin for preview
        add_action('admin_head', [$this, 'output_css_variables'], 100);
        
        // Register AJAX handlers
        add_action('wp_ajax_aisb_save_primary_color', [$this, 'ajax_save_primary_color']);
        add_action('wp_ajax_aisb_reset_primary_color', [$this, 'ajax_reset_primary_color']);
    }
    
    /**
     * Get the primary color (saved or default)
     */
    public function get_primary_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['primary_color']) ? $settings['primary_color'] : $this->default_primary;
    }
    
    /**
     * Save primary color via AJAX
     */
    public function ajax_save_primary_color() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Validate color
        $color = isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '';
        
        if (empty($color)) {
            wp_send_json_error(['message' => __('Invalid color format', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update primary color
        $settings['primary_color'] = $color;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        $updated = update_option(self::OPTION_NAME, $settings);
        
        if ($updated) {
            // Clear any cached CSS if we implement caching later
            wp_send_json_success([
                'message' => __('Primary color saved successfully', 'ai-section-builder'),
                'color' => $color
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to save color', 'ai-section-builder')]);
        }
    }
    
    /**
     * Reset primary color to default via AJAX
     */
    public function ajax_reset_primary_color() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Reset to default
        $settings['primary_color'] = $this->default_primary;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        $updated = update_option(self::OPTION_NAME, $settings);
        
        if ($updated !== false) {
            wp_send_json_success([
                'message' => __('Primary color reset to default', 'ai-section-builder'),
                'color' => $this->default_primary
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to reset color', 'ai-section-builder')]);
        }
    }
    
    /**
     * Output CSS variables to page
     */
    public function output_css_variables() {
        $primary_color = $this->get_primary_color();
        $primary_hover = $this->darken_color($primary_color, 10);
        
        // Light mode defaults
        $light_defaults = [
            'base' => '#ffffff',
            'text' => '#1a1a1a',
            'muted' => '#64748b',
            'secondary' => '#f1f5f9',
            'border' => '#e2e8f0',
            'success' => '#10b981',
            'error' => '#ef4444'
        ];
        
        // Dark mode defaults
        $dark_defaults = [
            'dark_base' => '#1a1a1a',
            'dark_text' => '#fafafa',
            'dark_muted' => '#9ca3af',
            'dark_secondary' => '#374151',
            'dark_border' => '#4b5563'
        ];
        
        // For dark mode primary, we'll use a lighter variant
        $dark_primary = $this->lighten_color($primary_color, 20);
        $dark_primary_hover = $this->lighten_color($primary_color, 30);
        
        ?>
        <style id="aisb-color-settings">
            :root {
                /* Light Mode Colors */
                --aisb-color-base: <?php echo esc_attr($light_defaults['base']); ?>;
                --aisb-color-text: <?php echo esc_attr($light_defaults['text']); ?>;
                --aisb-color-muted: <?php echo esc_attr($light_defaults['muted']); ?>;
                --aisb-color-secondary: <?php echo esc_attr($light_defaults['secondary']); ?>;
                --aisb-color-border: <?php echo esc_attr($light_defaults['border']); ?>;
                --aisb-color-success: <?php echo esc_attr($light_defaults['success']); ?>;
                --aisb-color-error: <?php echo esc_attr($light_defaults['error']); ?>;
                
                /* Primary color (customizable) */
                --aisb-color-primary: <?php echo esc_attr($primary_color); ?>;
                --aisb-color-primary-hover: <?php echo esc_attr($primary_hover); ?>;
                
                /* Dark Mode Colors */
                --aisb-color-dark-base: <?php echo esc_attr($dark_defaults['dark_base']); ?>;
                --aisb-color-dark-text: <?php echo esc_attr($dark_defaults['dark_text']); ?>;
                --aisb-color-dark-muted: <?php echo esc_attr($dark_defaults['dark_muted']); ?>;
                --aisb-color-dark-secondary: <?php echo esc_attr($dark_defaults['dark_secondary']); ?>;
                --aisb-color-dark-border: <?php echo esc_attr($dark_defaults['dark_border']); ?>;
                
                /* Dark mode primary (lighter variant for dark backgrounds) */
                --aisb-color-dark-primary: <?php echo esc_attr($dark_primary); ?>;
                --aisb-color-dark-primary-hover: <?php echo esc_attr($dark_primary_hover); ?>;
                
                /* Semantic mappings for light mode */
                --aisb-interactive-primary: <?php echo esc_attr($primary_color); ?>;
                --aisb-interactive-primary-hover: <?php echo esc_attr($primary_hover); ?>;
                --aisb-interactive-primary-text: #ffffff;
                --aisb-interactive-secondary: <?php echo esc_attr($primary_color); ?>;
                --aisb-interactive-secondary-hover: <?php echo esc_attr($primary_color); ?>;
                --aisb-interactive-secondary-text: <?php echo esc_attr($primary_color); ?>;
                --aisb-content-link: <?php echo esc_attr($primary_color); ?>;
                --aisb-content-link-hover: <?php echo esc_attr($primary_hover); ?>;
                --aisb-content-link-visited: <?php echo esc_attr($light_defaults['muted']); ?>;
                --aisb-border-interactive: <?php echo esc_attr($primary_color); ?>;
                --aisb-feedback-info: <?php echo esc_attr($primary_color); ?>;
                
                /* Surface variables */
                --aisb-surface-primary: <?php echo esc_attr($light_defaults['base']); ?>;
                --aisb-surface-secondary: <?php echo esc_attr($light_defaults['secondary']); ?>;
                --aisb-surface-elevated: <?php echo esc_attr($light_defaults['base']); ?>;
                --aisb-surface-overlay: rgba(0, 0, 0, 0.05);
                
                /* Content variables */
                --aisb-content-primary: <?php echo esc_attr($light_defaults['text']); ?>;
                --aisb-content-secondary: <?php echo esc_attr($light_defaults['muted']); ?>;
                --aisb-content-inverse: <?php echo esc_attr($light_defaults['base']); ?>;
                
                /* Border variables */
                --aisb-border-primary: <?php echo esc_attr($light_defaults['border']); ?>;
                --aisb-border-secondary: <?php echo esc_attr($light_defaults['border']); ?>;
            }
        </style>
        <?php
    }
    
    /**
     * Darken a hex color by a percentage
     */
    private function darken_color($hex, $percent) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Darken
        $r = max(0, round($r * (100 - $percent) / 100));
        $g = max(0, round($g * (100 - $percent) / 100));
        $b = max(0, round($b * (100 - $percent) / 100));
        
        // Convert back to hex
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Lighten a hex color by a percentage
     */
    private function lighten_color($hex, $percent) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Lighten
        $r = min(255, round($r + (255 - $r) * $percent / 100));
        $g = min(255, round($g + (255 - $g) * $percent / 100));
        $b = min(255, round($b + (255 - $b) * $percent / 100));
        
        // Convert back to hex
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
}

// Initialize singleton
\AISB\Settings\Color_Settings::get_instance();
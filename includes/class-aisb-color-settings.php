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
     * Default base background color for light mode
     */
    private $default_base_light = '#ffffff';
    
    /**
     * Default base background color for dark mode
     */
    private $default_base_dark = '#1a1a1a';
    
    /**
     * Default text color for light mode
     */
    private $default_text_light = '#1a1a1a';
    
    /**
     * Default text color for dark mode
     */
    private $default_text_dark = '#fafafa';
    
    /**
     * Default secondary background color for light mode
     */
    private $default_secondary_light = '#f1f5f9';
    
    /**
     * Default secondary background color for dark mode
     */
    private $default_secondary_dark = '#374151';
    
    /**
     * Default border color for light mode
     */
    private $default_border_light = '#e2e8f0';
    
    /**
     * Default border color for dark mode
     */
    private $default_border_dark = '#4b5563';
    
    /**
     * Default muted text color for light mode
     */
    private $default_muted_light = '#64748b';
    
    /**
     * Default muted text color for dark mode
     */
    private $default_muted_dark = '#9ca3af';
    
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
        add_action('wp_ajax_aisb_save_text_colors', [$this, 'ajax_save_text_colors']);
        add_action('wp_ajax_aisb_reset_text_colors', [$this, 'ajax_reset_text_colors']);
        // Base background color handlers
        add_action('wp_ajax_aisb_save_base_colors', [$this, 'ajax_save_base_colors']);
        add_action('wp_ajax_aisb_reset_base_colors', [$this, 'ajax_reset_base_colors']);
        // Secondary background color handlers
        add_action('wp_ajax_aisb_save_secondary_colors', [$this, 'ajax_save_secondary_colors']);
        add_action('wp_ajax_aisb_reset_secondary_colors', [$this, 'ajax_reset_secondary_colors']);
        // Border color handlers
        add_action('wp_ajax_aisb_save_border_colors', [$this, 'ajax_save_border_colors']);
        add_action('wp_ajax_aisb_reset_border_colors', [$this, 'ajax_reset_border_colors']);
        // New unified save handler
        add_action('wp_ajax_aisb_save_all_colors', [$this, 'ajax_save_all_colors']);
    }
    
    /**
     * Get the primary color (saved or default)
     */
    public function get_primary_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['primary_color']) ? $settings['primary_color'] : $this->default_primary;
    }
    
    /**
     * Get the base background color for light mode (saved or default)
     */
    public function get_base_light_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['base_light_color']) ? $settings['base_light_color'] : $this->default_base_light;
    }
    
    /**
     * Get the base background color for dark mode (saved or default)
     */
    public function get_base_dark_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['base_dark_color']) ? $settings['base_dark_color'] : $this->default_base_dark;
    }
    
    /**
     * Get the text color for light mode (saved or default)
     */
    public function get_text_light_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['text_light_color']) ? $settings['text_light_color'] : $this->default_text_light;
    }
    
    /**
     * Get the text color for dark mode (saved or default)
     */
    public function get_text_dark_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['text_dark_color']) ? $settings['text_dark_color'] : $this->default_text_dark;
    }
    
    /**
     * Get the secondary background color for light mode (saved or default)
     */
    public function get_secondary_light_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['secondary_light_color']) ? $settings['secondary_light_color'] : $this->default_secondary_light;
    }
    
    /**
     * Get the secondary background color for dark mode (saved or default)
     */
    public function get_secondary_dark_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['secondary_dark_color']) ? $settings['secondary_dark_color'] : $this->default_secondary_dark;
    }
    
    /**
     * Get the border color for light mode (saved or default)
     */
    public function get_border_light_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['border_light_color']) ? $settings['border_light_color'] : $this->default_border_light;
    }
    
    /**
     * Get the border color for dark mode (saved or default)
     */
    public function get_border_dark_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['border_dark_color']) ? $settings['border_dark_color'] : $this->default_border_dark;
    }
    
    /**
     * Get the muted text color for light mode (saved or default)
     */
    public function get_muted_light_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['muted_light_color']) ? $settings['muted_light_color'] : $this->default_muted_light;
    }
    
    /**
     * Get the muted text color for dark mode (saved or default)
     */
    public function get_muted_dark_color() {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings['muted_dark_color']) ? $settings['muted_dark_color'] : $this->default_muted_dark;
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
        
        // Save to database (update_option returns false if value unchanged, which is ok)
        update_option(self::OPTION_NAME, $settings);
        
        // Always return success if we got this far
        wp_send_json_success([
            'message' => __('Primary color saved successfully', 'ai-section-builder'),
            'color' => $color
        ]);
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
        
        // Save to database (update_option returns false if value unchanged, which is ok)
        update_option(self::OPTION_NAME, $settings);
        
        // Always return success if we got this far
        wp_send_json_success([
            'message' => __('Primary color reset to default', 'ai-section-builder'),
            'color' => $this->default_primary
        ]);
    }
    
    /**
     * Save text colors via AJAX
     */
    public function ajax_save_text_colors() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Validate colors
        $text_light = isset($_POST['text_light_color']) ? sanitize_hex_color($_POST['text_light_color']) : '';
        $text_dark = isset($_POST['text_dark_color']) ? sanitize_hex_color($_POST['text_dark_color']) : '';
        
        if (empty($text_light) || empty($text_dark)) {
            wp_send_json_error(['message' => __('Invalid color format', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update text colors
        $settings['text_light_color'] = $text_light;
        $settings['text_dark_color'] = $text_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database (update_option returns false if value unchanged, which is ok)
        update_option(self::OPTION_NAME, $settings);
        
        // Always return success if we got this far
        wp_send_json_success([
            'message' => __('Text colors saved successfully', 'ai-section-builder'),
            'text_light' => $text_light,
            'text_dark' => $text_dark
        ]);
    }
    
    /**
     * Reset text colors to defaults via AJAX
     */
    public function ajax_reset_text_colors() {
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
        
        // Reset to defaults
        $settings['text_light_color'] = $this->default_text_light;
        $settings['text_dark_color'] = $this->default_text_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database (update_option returns false if value unchanged, which is ok)
        update_option(self::OPTION_NAME, $settings);
        
        // Always return success if we got this far
        wp_send_json_success([
            'message' => __('Text colors reset to defaults', 'ai-section-builder'),
            'text_light' => $this->default_text_light,
            'text_dark' => $this->default_text_dark
        ]);
    }
    
    /**
     * AJAX handler to save base background colors
     */
    public function ajax_save_base_colors() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Validate colors
        $base_light = isset($_POST['base_light_color']) ? sanitize_hex_color($_POST['base_light_color']) : '';
        $base_dark = isset($_POST['base_dark_color']) ? sanitize_hex_color($_POST['base_dark_color']) : '';
        
        if (empty($base_light) || empty($base_dark)) {
            wp_send_json_error(['message' => __('Invalid color format', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update base colors
        $settings['base_light_color'] = $base_light;
        $settings['base_dark_color'] = $base_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return success
        wp_send_json_success([
            'base_light_color' => $base_light,
            'base_dark_color' => $base_dark,
            'message' => __('Background colors saved successfully', 'ai-section-builder')
        ]);
    }
    
    /**
     * AJAX handler to reset base background colors to defaults
     */
    public function ajax_reset_base_colors() {
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
        
        // Reset to defaults
        $settings['base_light_color'] = $this->default_base_light;
        $settings['base_dark_color'] = $this->default_base_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return defaults
        wp_send_json_success([
            'base_light_color' => $this->default_base_light,
            'base_dark_color' => $this->default_base_dark,
            'message' => __('Background colors reset to defaults', 'ai-section-builder')
        ]);
    }
    
    /**
     * Save secondary background colors via AJAX
     */
    public function ajax_save_secondary_colors() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Validate colors
        $secondary_light = isset($_POST['secondary_light_color']) ? sanitize_hex_color($_POST['secondary_light_color']) : '';
        $secondary_dark = isset($_POST['secondary_dark_color']) ? sanitize_hex_color($_POST['secondary_dark_color']) : '';
        
        if (empty($secondary_light) || empty($secondary_dark)) {
            wp_send_json_error(['message' => __('Invalid color format', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update secondary colors
        $settings['secondary_light_color'] = $secondary_light;
        $settings['secondary_dark_color'] = $secondary_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return success
        wp_send_json_success([
            'message' => __('Secondary background colors saved successfully', 'ai-section-builder'),
            'secondary_light' => $secondary_light,
            'secondary_dark' => $secondary_dark
        ]);
    }
    
    /**
     * Reset secondary background colors to defaults via AJAX
     */
    public function ajax_reset_secondary_colors() {
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
        
        // Reset to defaults
        $settings['secondary_light_color'] = $this->default_secondary_light;
        $settings['secondary_dark_color'] = $this->default_secondary_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return success
        wp_send_json_success([
            'message' => __('Secondary background colors reset to defaults', 'ai-section-builder'),
            'secondary_light' => $this->default_secondary_light,
            'secondary_dark' => $this->default_secondary_dark
        ]);
    }
    
    /**
     * Save border colors via AJAX
     */
    public function ajax_save_border_colors() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Validate colors
        $border_light = isset($_POST['border_light_color']) ? sanitize_hex_color($_POST['border_light_color']) : '';
        $border_dark = isset($_POST['border_dark_color']) ? sanitize_hex_color($_POST['border_dark_color']) : '';
        
        if (empty($border_light) || empty($border_dark)) {
            wp_send_json_error(['message' => __('Invalid color format', 'ai-section-builder')]);
        }
        
        // Get existing settings
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update border colors
        $settings['border_light_color'] = $border_light;
        $settings['border_dark_color'] = $border_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return success
        wp_send_json_success([
            'message' => __('Border colors saved successfully', 'ai-section-builder'),
            'border_light' => $border_light,
            'border_dark' => $border_dark
        ]);
    }
    
    /**
     * Reset border colors to defaults via AJAX
     */
    public function ajax_reset_border_colors() {
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
        
        // Reset to defaults
        $settings['border_light_color'] = $this->default_border_light;
        $settings['border_dark_color'] = $this->default_border_dark;
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return success
        wp_send_json_success([
            'message' => __('Border colors reset to defaults', 'ai-section-builder'),
            'border_light' => $this->default_border_light,
            'border_dark' => $this->default_border_dark
        ]);
    }
    
    /**
     * Save all colors at once via AJAX
     */
    public function ajax_save_all_colors() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_color_settings')) {
            wp_send_json_error(['message' => __('Security check failed', 'ai-section-builder')]);
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ai-section-builder')]);
        }
        
        // Get existing settings - IMPORTANT: preserve all existing values
        $settings = get_option(self::OPTION_NAME, []);
        
        // Update colors if provided
        if (isset($_POST['primary_color'])) {
            $primary = sanitize_hex_color($_POST['primary_color']);
            if ($primary) {
                $settings['primary_color'] = $primary;
            }
        }
        
        if (isset($_POST['base_light_color'])) {
            $base_light = sanitize_hex_color($_POST['base_light_color']);
            if ($base_light) {
                $settings['base_light_color'] = $base_light;
            }
        }
        
        if (isset($_POST['base_dark_color'])) {
            $base_dark = sanitize_hex_color($_POST['base_dark_color']);
            if ($base_dark) {
                $settings['base_dark_color'] = $base_dark;
            }
        }
        
        if (isset($_POST['text_light_color'])) {
            $text_light = sanitize_hex_color($_POST['text_light_color']);
            if ($text_light) {
                $settings['text_light_color'] = $text_light;
            }
        }
        
        if (isset($_POST['text_dark_color'])) {
            $text_dark = sanitize_hex_color($_POST['text_dark_color']);
            if ($text_dark) {
                $settings['text_dark_color'] = $text_dark;
            }
        }
        
        if (isset($_POST['secondary_light_color'])) {
            $secondary_light = sanitize_hex_color($_POST['secondary_light_color']);
            if ($secondary_light) {
                $settings['secondary_light_color'] = $secondary_light;
            }
        }
        
        if (isset($_POST['secondary_dark_color'])) {
            $secondary_dark = sanitize_hex_color($_POST['secondary_dark_color']);
            if ($secondary_dark) {
                $settings['secondary_dark_color'] = $secondary_dark;
            }
        }
        
        if (isset($_POST['border_light_color'])) {
            $border_light = sanitize_hex_color($_POST['border_light_color']);
            if ($border_light) {
                $settings['border_light_color'] = $border_light;
            }
        }
        
        if (isset($_POST['border_dark_color'])) {
            $border_dark = sanitize_hex_color($_POST['border_dark_color']);
            if ($border_dark) {
                $settings['border_dark_color'] = $border_dark;
            }
        }
        
        if (isset($_POST['muted_light_color'])) {
            $muted_light = sanitize_hex_color($_POST['muted_light_color']);
            if ($muted_light) {
                $settings['muted_light_color'] = $muted_light;
            }
        }
        
        if (isset($_POST['muted_dark_color'])) {
            $muted_dark = sanitize_hex_color($_POST['muted_dark_color']);
            if ($muted_dark) {
                $settings['muted_dark_color'] = $muted_dark;
            }
        }
        
        // Update metadata
        $settings['version'] = self::VERSION;
        $settings['last_updated'] = current_time('mysql');
        
        // Save to database
        update_option(self::OPTION_NAME, $settings);
        
        // Return all saved colors
        wp_send_json_success([
            'message' => __('Colors saved successfully', 'ai-section-builder'),
            'colors' => [
                'primary' => $settings['primary_color'] ?? $this->default_primary,
                'base_light' => $settings['base_light_color'] ?? $this->default_base_light,
                'base_dark' => $settings['base_dark_color'] ?? $this->default_base_dark,
                'text_light' => $settings['text_light_color'] ?? $this->default_text_light,
                'text_dark' => $settings['text_dark_color'] ?? $this->default_text_dark,
                'secondary_light' => $settings['secondary_light_color'] ?? $this->default_secondary_light,
                'secondary_dark' => $settings['secondary_dark_color'] ?? $this->default_secondary_dark,
                'border_light' => $settings['border_light_color'] ?? $this->default_border_light,
                'border_dark' => $settings['border_dark_color'] ?? $this->default_border_dark,
                'muted_light' => $settings['muted_light_color'] ?? $this->default_muted_light,
                'muted_dark' => $settings['muted_dark_color'] ?? $this->default_muted_dark
            ]
        ]);
    }
    
    /**
     * Output CSS variables to page
     */
    public function output_css_variables() {
        $primary_color = $this->get_primary_color();
        $primary_hover = $this->darken_color($primary_color, 10);
        
        // Get saved base background colors
        $base_light_color = $this->get_base_light_color();
        $base_dark_color = $this->get_base_dark_color();
        
        // Get saved text colors
        $text_light_color = $this->get_text_light_color();
        $text_dark_color = $this->get_text_dark_color();
        
        // Get saved secondary background colors
        $secondary_light_color = $this->get_secondary_light_color();
        $secondary_dark_color = $this->get_secondary_dark_color();
        
        // Get saved border colors
        $border_light_color = $this->get_border_light_color();
        $border_dark_color = $this->get_border_dark_color();
        
        // Get saved muted text colors
        $muted_light_color = $this->get_muted_light_color();
        $muted_dark_color = $this->get_muted_dark_color();
        
        // Light mode defaults (use saved colors where applicable)
        $light_defaults = [
            'base' => $base_light_color,  // Use saved base color
            'text' => $text_light_color,  // Use saved text color
            'muted' => $muted_light_color,  // Use saved muted color
            'secondary' => $secondary_light_color,  // Use saved secondary color
            'border' => $border_light_color,  // Use saved border color
            'success' => '#10b981',
            'error' => '#ef4444'
        ];
        
        // Dark mode defaults (use saved colors where applicable)
        $dark_defaults = [
            'dark_base' => $base_dark_color,  // Use saved base color
            'dark_text' => $text_dark_color,  // Use saved text color
            'dark_muted' => $muted_dark_color,  // Use saved muted color
            'dark_secondary' => $secondary_dark_color,  // Use saved secondary color
            'dark_border' => $border_dark_color  // Use saved border color
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
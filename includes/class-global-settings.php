<?php
/**
 * Global Settings Management Class
 * 
 * Handles storage, validation, and retrieval of global design settings
 * 
 * @package AISB
 * @since 2.0.0
 */

namespace AISB;

if (!defined('ABSPATH')) {
    exit;
}

class Global_Settings {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'aisb_global_settings';
    
    /**
     * Settings version for migration support
     */
    const VERSION = '1.0.0';
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Current settings cache
     */
    private $settings = null;
    
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
        // Inject CSS variables into frontend and editor
        add_action('wp_head', [$this, 'inject_css_variables'], 5);
        add_action('admin_head', [$this, 'inject_css_variables'], 5);
        
        // Register AJAX handlers
        add_action('wp_ajax_aisb_save_global_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_aisb_get_global_settings', [$this, 'ajax_get_settings']);
        add_action('wp_ajax_aisb_reset_global_settings', [$this, 'ajax_reset_settings']);
    }
    
    /**
     * Get default settings with constraints
     */
    public function get_defaults() {
        return [
            'version' => self::VERSION,
            'colors' => [
                'base' => '#ffffff',
                'text' => '#1a1a1a',
                'muted' => '#64748b',
                'primary' => '#2563eb',
                'primary_hover' => '#1d4ed8',
                'secondary' => '#f1f5f9',
                'border' => '#e2e8f0',
                'success' => '#10b981',
                'error' => '#ef4444',
                'dark_base' => '#1a1a1a',
                'dark_text' => '#fafafa',
                'dark_muted' => '#9ca3af',
                'dark_primary' => '#60a5fa',
                'dark_primary_hover' => '#3b82f6',
                'dark_secondary' => '#374151',
                'dark_border' => '#4b5563'
            ],
            'typography' => [
                'font_heading' => 'Inter',
                'font_body' => 'system-ui',
                'size_base' => 16,
                'scale_ratio' => 1.25,
                'line_height_body' => 1.6,
                'line_height_heading' => 1.2
            ],
            'layout' => [
                'container_width' => 1200,
                'section_padding' => 80,
                'element_spacing' => 24,
                'breakpoint_tablet' => 768,
                'breakpoint_mobile' => 480
            ]
        ];
    }
    
    /**
     * Get validation rules with constraints
     */
    public function get_validation_rules() {
        return [
            'colors' => [
                'base' => ['type' => 'color'],
                'text' => ['type' => 'color'],
                'muted' => ['type' => 'color'],
                'primary' => ['type' => 'color'],
                'primary_hover' => ['type' => 'color'],
                'secondary' => ['type' => 'color'],
                'border' => ['type' => 'color'],
                'success' => ['type' => 'color'],
                'error' => ['type' => 'color'],
                'dark_base' => ['type' => 'color'],
                'dark_text' => ['type' => 'color'],
                'dark_muted' => ['type' => 'color'],
                'dark_primary' => ['type' => 'color'],
                'dark_primary_hover' => ['type' => 'color'],
                'dark_secondary' => ['type' => 'color'],
                'dark_border' => ['type' => 'color']
            ],
            'typography' => [
                'font_heading' => ['type' => 'string', 'max_length' => 100],
                'font_body' => ['type' => 'string', 'max_length' => 100],
                'size_base' => ['type' => 'number', 'min' => 12, 'max' => 20],
                'scale_ratio' => ['type' => 'number', 'min' => 1.125, 'max' => 1.5],
                'line_height_body' => ['type' => 'number', 'min' => 1.2, 'max' => 2.0],
                'line_height_heading' => ['type' => 'number', 'min' => 1.0, 'max' => 1.8]
            ],
            'layout' => [
                'container_width' => ['type' => 'number', 'min' => 960, 'max' => 1920],
                'section_padding' => ['type' => 'number', 'min' => 20, 'max' => 200],
                'element_spacing' => ['type' => 'number', 'min' => 8, 'max' => 80],
                'breakpoint_tablet' => ['type' => 'number', 'min' => 640, 'max' => 1024],
                'breakpoint_mobile' => ['type' => 'number', 'min' => 320, 'max' => 640]
            ]
        ];
    }
    
    /**
     * Get current settings
     */
    public function get_settings() {
        if (null === $this->settings) {
            $stored = get_option(self::OPTION_NAME);
            
            if (false === $stored) {
                $this->settings = $this->get_defaults();
            } else {
                // Merge with defaults to ensure all keys exist
                $this->settings = wp_parse_args_recursive($stored, $this->get_defaults());
            }
        }
        
        return $this->settings;
    }
    
    /**
     * Save settings with validation
     */
    public function save_settings($new_settings) {
        $validated = $this->validate_settings($new_settings);
        
        if (is_wp_error($validated)) {
            return $validated;
        }
        
        // Add version
        $validated['version'] = self::VERSION;
        
        // Save to database
        $result = update_option(self::OPTION_NAME, $validated);
        
        // Clear cache
        $this->settings = null;
        
        // Clear any CSS cache
        $this->clear_css_cache();
        
        return $result;
    }
    
    /**
     * Validate settings against rules
     */
    private function validate_settings($settings) {
        $rules = $this->get_validation_rules();
        $errors = [];
        $validated = [];
        
        foreach ($rules as $section => $section_rules) {
            if (!isset($settings[$section])) {
                $errors[] = sprintf('Missing section: %s', $section);
                continue;
            }
            
            $validated[$section] = [];
            
            foreach ($section_rules as $field => $constraints) {
                if (!isset($settings[$section][$field])) {
                    $errors[] = sprintf('Missing field: %s.%s', $section, $field);
                    continue;
                }
                
                $value = $settings[$section][$field];
                $validation_result = $this->validate_field($value, $constraints);
                
                if (is_wp_error($validation_result)) {
                    $errors[] = sprintf('%s.%s: %s', $section, $field, $validation_result->get_error_message());
                } else {
                    $validated[$section][$field] = $validation_result;
                }
            }
        }
        
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', implode(', ', $errors));
        }
        
        return $validated;
    }
    
    /**
     * Validate individual field
     */
    private function validate_field($value, $constraints) {
        $type = $constraints['type'] ?? 'string';
        
        switch ($type) {
            case 'color':
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    return new \WP_Error('invalid_color', 'Invalid hex color format');
                }
                return $value;
                
            case 'number':
                $value = is_numeric($value) ? floatval($value) : 0;
                
                if (isset($constraints['min']) && $value < $constraints['min']) {
                    return new \WP_Error('below_minimum', sprintf('Value must be at least %s', $constraints['min']));
                }
                
                if (isset($constraints['max']) && $value > $constraints['max']) {
                    return new \WP_Error('above_maximum', sprintf('Value must be at most %s', $constraints['max']));
                }
                
                return $value;
                
            case 'string':
                $value = sanitize_text_field($value);
                
                if (isset($constraints['max_length']) && strlen($value) > $constraints['max_length']) {
                    return new \WP_Error('too_long', sprintf('Maximum length is %d characters', $constraints['max_length']));
                }
                
                return $value;
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset_settings() {
        delete_option(self::OPTION_NAME);
        $this->settings = null;
        $this->clear_css_cache();
        return true;
    }
    
    /**
     * Inject CSS variables into page
     */
    public function inject_css_variables() {
        $settings = $this->get_settings();
        
        // Calculate derived values
        $base_size = $settings['typography']['size_base'];
        $scale = $settings['typography']['scale_ratio'];
        
        $h6_size = $base_size * $scale;
        $h5_size = $h6_size * $scale;
        $h4_size = $h5_size * $scale;
        $h3_size = $h4_size * $scale;
        $h2_size = $h3_size * $scale;
        $h1_size = $h2_size * $scale;
        
        ?>
        <style id="aisb-global-settings-css">
            :root {
                /* Global Settings - Override Core Design Tokens */
                --aisb-color-base: <?php echo esc_attr($settings['colors']['base']); ?>;
                --aisb-color-text: <?php echo esc_attr($settings['colors']['text']); ?>;
                --aisb-color-muted: <?php echo esc_attr($settings['colors']['muted']); ?>;
                --aisb-color-primary: <?php echo esc_attr($settings['colors']['primary']); ?>;
                --aisb-color-primary-hover: <?php echo esc_attr($settings['colors']['primary_hover']); ?>;
                --aisb-color-secondary: <?php echo esc_attr($settings['colors']['secondary']); ?>;
                --aisb-color-border: <?php echo esc_attr($settings['colors']['border']); ?>;
                --aisb-color-success: <?php echo esc_attr($settings['colors']['success']); ?>;
                --aisb-color-error: <?php echo esc_attr($settings['colors']['error']); ?>;
                --aisb-color-dark-base: <?php echo esc_attr($settings['colors']['dark_base']); ?>;
                --aisb-color-dark-text: <?php echo esc_attr($settings['colors']['dark_text']); ?>;
                --aisb-color-dark-muted: <?php echo esc_attr($settings['colors']['dark_muted']); ?>;
                --aisb-color-dark-primary: <?php echo esc_attr($settings['colors']['dark_primary']); ?>;
                --aisb-color-dark-primary-hover: <?php echo esc_attr($settings['colors']['dark_primary_hover']); ?>;
                --aisb-color-dark-secondary: <?php echo esc_attr($settings['colors']['dark_secondary']); ?>;
                --aisb-color-dark-border: <?php echo esc_attr($settings['colors']['dark_border']); ?>;
                
                /* Global Settings - Typography */
                --aisb-gs-font-heading: <?php echo esc_attr($settings['typography']['font_heading']); ?>;
                --aisb-gs-font-body: <?php echo esc_attr($settings['typography']['font_body']); ?>;
                --aisb-gs-size-base: <?php echo esc_attr($settings['typography']['size_base']); ?>px;
                --aisb-gs-scale-ratio: <?php echo esc_attr($settings['typography']['scale_ratio']); ?>;
                --aisb-gs-line-height-body: <?php echo esc_attr($settings['typography']['line_height_body']); ?>;
                --aisb-gs-line-height-heading: <?php echo esc_attr($settings['typography']['line_height_heading']); ?>;
                
                /* Calculated heading sizes */
                --aisb-gs-size-h1: <?php echo round($h1_size); ?>px;
                --aisb-gs-size-h2: <?php echo round($h2_size); ?>px;
                --aisb-gs-size-h3: <?php echo round($h3_size); ?>px;
                --aisb-gs-size-h4: <?php echo round($h4_size); ?>px;
                --aisb-gs-size-h5: <?php echo round($h5_size); ?>px;
                --aisb-gs-size-h6: <?php echo round($h6_size); ?>px;
                
                /* Global Settings - Layout */
                --aisb-gs-container-width: <?php echo esc_attr($settings['layout']['container_width']); ?>px;
                --aisb-gs-section-padding: <?php echo esc_attr($settings['layout']['section_padding']); ?>px;
                --aisb-gs-element-spacing: <?php echo esc_attr($settings['layout']['element_spacing']); ?>px;
                --aisb-gs-breakpoint-tablet: <?php echo esc_attr($settings['layout']['breakpoint_tablet']); ?>px;
                --aisb-gs-breakpoint-mobile: <?php echo esc_attr($settings['layout']['breakpoint_mobile']); ?>px;
            }
        </style>
        <?php
    }
    
    /**
     * Clear CSS cache
     */
    private function clear_css_cache() {
        // Hook for cache plugins to clear their CSS cache
        do_action('aisb_global_settings_updated');
        
        // Clear any transients we might use in the future
        delete_transient('aisb_css_cache');
    }
    
    /**
     * AJAX handler: Save settings
     */
    public function ajax_save_settings() {
        // Check nonce
        if (!check_ajax_referer('aisb_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get and validate settings
        $settings = json_decode(stripslashes($_POST['settings'] ?? '{}'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data');
            return;
        }
        
        // Save settings
        $result = $this->save_settings($settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success([
            'message' => 'Settings saved successfully',
            'settings' => $this->get_settings()
        ]);
    }
    
    /**
     * AJAX handler: Get settings
     */
    public function ajax_get_settings() {
        // Check nonce
        if (!check_ajax_referer('aisb_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        wp_send_json_success([
            'settings' => $this->get_settings(),
            'defaults' => $this->get_defaults(),
            'rules' => $this->get_validation_rules()
        ]);
    }
    
    /**
     * AJAX handler: Reset settings
     */
    public function ajax_reset_settings() {
        // Check nonce
        if (!check_ajax_referer('aisb_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $this->reset_settings();
        
        wp_send_json_success([
            'message' => 'Settings reset to defaults',
            'settings' => $this->get_settings()
        ]);
    }
}

// Initialize singleton
Global_Settings::get_instance();
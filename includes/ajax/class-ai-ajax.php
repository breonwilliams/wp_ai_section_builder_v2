<?php
/**
 * AI AJAX Handler Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Ajax;

use AISB\API\AI_Connector;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling AI-related AJAX requests
 */
class AI_Ajax {
    
    /**
     * Constructor - Register all AJAX handlers
     */
    public function __construct() {
        $this->register_ajax_handlers();
    }
    
    /**
     * Register all AJAX handlers for AI functionality
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_aisb_save_ai_settings', array($this, 'handle_save_ai_settings'));
        add_action('wp_ajax_aisb_test_ai_connection', array($this, 'handle_test_ai_connection'));
    }
    
    /**
     * Handle save AI settings AJAX request
     */
    public function handle_save_ai_settings() {
        // Check nonce - matching the form's nonce action
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_ai_settings')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        $keep_existing = isset($_POST['keep_existing_key']) && $_POST['keep_existing_key'] === '1';
        
        // Get current settings
        $current_settings = AI_Connector::get_settings();
        
        // Handle API key
        if ($keep_existing && isset($current_settings['api_key'])) {
            // Keep existing key
            $encrypted_key = $current_settings['api_key'];
        } else if (!empty($api_key)) {
            // Encrypt new key
            $encrypted_key = AI_Connector::encrypt_api_key($api_key);
        } else {
            // No key provided and not keeping existing
            $encrypted_key = '';
        }
        
        // Check if provider changed
        $provider_changed = isset($current_settings['provider']) && 
                          $current_settings['provider'] !== $provider;
        
        // Update settings with provider-specific fields
        $settings = array(
            'provider' => $provider,
            'api_key' => $encrypted_key, // Keep generic for backward compatibility
            'verified' => $provider_changed ? false : (isset($current_settings['verified']) ? $current_settings['verified'] : false),
            'last_verified' => $provider_changed ? 0 : (isset($current_settings['last_verified']) ? $current_settings['last_verified'] : 0)
        );
        
        // Add provider-specific fields
        if ($provider === 'openai') {
            $settings['openai_api_key'] = $encrypted_key;
            $settings['openai_model'] = $model;
        } else if ($provider === 'anthropic') {
            $settings['anthropic_api_key'] = $encrypted_key;
            $settings['anthropic_model'] = $model;
        }
        
        // Save settings
        $saved = AI_Connector::save_settings($settings);
        
        if ($saved) {
            wp_send_json_success(array(
                'message' => __('AI settings saved successfully!', 'ai-section-builder'),
                'verified' => $settings['verified']
            ));
        } else {
            wp_send_json_error(__('Failed to save AI settings.', 'ai-section-builder'));
        }
    }
    
    /**
     * Handle test AI connection AJAX request
     */
    public function handle_test_ai_connection() {
        // Check nonce - matching the form's nonce action
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_ai_settings')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        
        // If no API key provided, check if we should use existing
        if (empty($api_key)) {
            $settings = AI_Connector::get_settings();
            if (isset($settings['api_key'])) {
                $api_key = AI_Connector::decrypt_api_key($settings['api_key']);
            }
        }
        
        if (empty($api_key)) {
            wp_send_json_error(__('Please provide an API key.', 'ai-section-builder'));
        }
        
        // Test connection based on provider
        if ($provider === 'openai') {
            $result = AI_Connector::test_openai_connection($api_key, $model);
        } else if ($provider === 'anthropic') {
            $result = AI_Connector::test_anthropic_connection($api_key, $model);
        } else {
            wp_send_json_error(__('Invalid provider selected.', 'ai-section-builder'));
        }
        
        if ($result['success']) {
            // Update verification status
            $settings = AI_Connector::get_settings();
            $settings['verified'] = true;
            $settings['last_verified'] = time();
            
            // Ensure provider-specific API key is saved
            $settings['provider'] = $provider;
            $settings['api_key'] = AI_Connector::encrypt_api_key($api_key); // Keep generic too
            
            if ($provider === 'openai') {
                $settings['openai_api_key'] = AI_Connector::encrypt_api_key($api_key);
                $settings['openai_model'] = $model;
            } else if ($provider === 'anthropic') {
                $settings['anthropic_api_key'] = AI_Connector::encrypt_api_key($api_key);
                $settings['anthropic_model'] = $model;
            }
            
            AI_Connector::save_settings($settings);
            
            wp_send_json_success(array(
                'message' => $result['message'],
                'verified' => true
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
}
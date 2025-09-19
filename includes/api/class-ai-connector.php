<?php
/**
 * AI API Connector Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\API;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Connector class for OpenAI and Anthropic APIs
 */
class AI_Connector {
    
    /**
     * Encrypt API key for secure storage
     *
     * @param string $api_key API key to encrypt
     * @return string Encrypted API key
     */
    public static function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }
        
        // Check if encryption constants are defined
        if (!defined('AISB_ENCRYPTION_KEY') || !defined('AISB_ENCRYPTION_SALT')) {
            // If not defined, store in plain text with warning (for development only)
            // In production, these constants should always be defined
            return base64_encode($api_key);
        }
        
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', AISB_ENCRYPTION_KEY), 0, 32);
        $iv = substr(hash('sha256', AISB_ENCRYPTION_SALT), 0, 16);
        
        return base64_encode(openssl_encrypt($api_key, $method, $key, 0, $iv));
    }
    
    /**
     * Decrypt API key for use
     *
     * @param string $encrypted_key Encrypted API key
     * @return string Decrypted API key
     */
    public static function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }
        
        // Check if encryption constants are defined
        if (!defined('AISB_ENCRYPTION_KEY') || !defined('AISB_ENCRYPTION_SALT')) {
            // If not defined, assume it was stored as base64 only
            return base64_decode($encrypted_key);
        }
        
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', AISB_ENCRYPTION_KEY), 0, 32);
        $iv = substr(hash('sha256', AISB_ENCRYPTION_SALT), 0, 16);
        
        return openssl_decrypt(base64_decode($encrypted_key), $method, $key, 0, $iv);
    }
    
    /**
     * Test OpenAI API connection
     *
     * @param string $api_key API key
     * @param string $model Model name (optional)
     * @return array Test result
     */
    public static function test_openai_connection($api_key, $model = '') {
        $url = 'https://api.openai.com/v1/models';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Connection error: %s', 'ai-section-builder'), $response->get_error_message())
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => __('Successfully connected to OpenAI API.', 'ai-section-builder')
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key. Please check your OpenAI API key.', 'ai-section-builder')
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('API returned error code: %d', 'ai-section-builder'), $status_code)
            );
        }
    }
    
    /**
     * Test Anthropic API connection
     *
     * @param string $api_key API key
     * @param string $model Model name (optional)
     * @return array Test result
     */
    public static function test_anthropic_connection($api_key, $model = '') {
        // Use a simple API call to test the connection
        $url = 'https://api.anthropic.com/v1/messages';
        
        // Create a minimal test message
        $body = array(
            'model' => $model ?: 'claude-3-haiku-20240307',
            'max_tokens' => 10,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hi'
                )
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Connection error: %s', 'ai-section-builder'), $response->get_error_message())
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => __('Successfully connected to Anthropic API.', 'ai-section-builder')
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key. Please check your Anthropic API key.', 'ai-section-builder')
            );
        } elseif ($status_code === 400) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('Invalid request.', 'ai-section-builder');
            return array(
                'success' => false,
                'message' => sprintf(__('API Error: %s', 'ai-section-builder'), $error_message)
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('API returned error code: %d', 'ai-section-builder'), $status_code)
            );
        }
    }
    
    /**
     * Get AI settings
     *
     * @return array Settings
     */
    public static function get_settings() {
        return get_option('aisb_ai_settings', array(
            'provider' => '',
            'api_key' => '',
            'model' => '',
            'verified' => false,
            'last_verified' => 0
        ));
    }
    
    /**
     * Save AI settings
     *
     * @param array $settings Settings to save
     * @return bool Success
     */
    public static function save_settings($settings) {
        return update_option('aisb_ai_settings', $settings);
    }
}
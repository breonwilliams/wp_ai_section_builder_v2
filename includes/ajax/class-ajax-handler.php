<?php
/**
 * Base AJAX Handler Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for AJAX handlers
 */
abstract class Ajax_Handler {
    
    /**
     * Action name
     *
     * @var string
     */
    protected $action = '';
    
    /**
     * Whether login is required
     *
     * @var bool
     */
    protected $requires_login = true;
    
    /**
     * Required capability
     *
     * @var string
     */
    protected $capability = 'edit_posts';
    
    /**
     * Nonce action name
     *
     * @var string
     */
    protected $nonce_action = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        if (empty($this->action)) {
            return;
        }
        
        // Register AJAX handlers
        if ($this->requires_login) {
            add_action('wp_ajax_' . $this->action, array($this, 'handle_request'));
        } else {
            add_action('wp_ajax_' . $this->action, array($this, 'handle_request'));
            add_action('wp_ajax_nopriv_' . $this->action, array($this, 'handle_request'));
        }
    }
    
    /**
     * Handle the AJAX request
     */
    public function handle_request() {
        // Check nonce if specified
        if (!empty($this->nonce_action)) {
            $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
            if (!wp_verify_nonce($nonce, $this->nonce_action)) {
                $this->send_error(__('Security check failed.', 'ai-section-builder'));
            }
        }
        
        // Check capabilities
        if ($this->requires_login && !is_user_logged_in()) {
            $this->send_error(__('You must be logged in to perform this action.', 'ai-section-builder'));
        }
        
        if (!empty($this->capability) && !current_user_can($this->capability)) {
            $this->send_error(__('You do not have permission to perform this action.', 'ai-section-builder'));
        }
        
        // Call the handler method
        $this->handle();
    }
    
    /**
     * Handle the specific AJAX request
     * Must be implemented by child classes
     */
    abstract protected function handle();
    
    /**
     * Send success response
     *
     * @param mixed $data Response data
     */
    protected function send_success($data = null) {
        wp_send_json_success($data);
    }
    
    /**
     * Send error response
     *
     * @param string $message Error message
     * @param mixed $data Additional data
     */
    protected function send_error($message = '', $data = null) {
        if (empty($message)) {
            $message = __('An error occurred.', 'ai-section-builder');
        }
        wp_send_json_error($message, 400);
    }
    
    /**
     * Get POST parameter
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_param($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Sanitize text field
     *
     * @param string $value Value to sanitize
     * @return string
     */
    protected function sanitize_text($value) {
        return sanitize_text_field($value);
    }
    
    /**
     * Sanitize textarea field
     *
     * @param string $value Value to sanitize
     * @return string
     */
    protected function sanitize_textarea($value) {
        return sanitize_textarea_field($value);
    }
}
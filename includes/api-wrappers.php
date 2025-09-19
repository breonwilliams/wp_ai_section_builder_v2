<?php
/**
 * API Function Wrappers for Backward Compatibility
 *
 * These wrapper functions maintain backward compatibility for API functions
 * that have been moved to classes.
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

use AISB\API\AI_Connector;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encrypt API key - wrapper for backward compatibility
 */
function aisb_encrypt_api_key($api_key) {
    return AI_Connector::encrypt_api_key($api_key);
}

/**
 * Decrypt API key - wrapper for backward compatibility
 */
function aisb_decrypt_api_key($encrypted_key) {
    return AI_Connector::decrypt_api_key($encrypted_key);
}

/**
 * Test OpenAI connection - wrapper for backward compatibility
 */
function aisb_test_openai_connection($api_key, $model = '') {
    return AI_Connector::test_openai_connection($api_key, $model);
}

/**
 * Test Anthropic connection - wrapper for backward compatibility
 */
function aisb_test_anthropic_connection($api_key, $model = '') {
    return AI_Connector::test_anthropic_connection($api_key, $model);
}
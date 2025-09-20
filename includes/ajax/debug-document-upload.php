<?php
/**
 * Debug helper for document upload
 * 
 * This file contains debugging utilities to help identify issues with document upload
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom error handler for document upload
 */
function aisb_document_upload_error_handler($errno, $errstr, $errfile, $errline) {
    // Log all errors during document upload
    error_log("AISB_DEBUG: PHP Error [$errno] $errstr in $errfile on line $errline");
    
    // Don't execute PHP internal error handler for non-fatal errors
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        return false;
    }
    
    return true;
}

/**
 * Custom exception handler for document upload
 */
function aisb_document_upload_exception_handler($exception) {
    error_log("AISB_DEBUG: Uncaught Exception: " . $exception->getMessage());
    error_log("AISB_DEBUG: File: " . $exception->getFile() . " Line: " . $exception->getLine());
    error_log("AISB_DEBUG: Stack trace: " . $exception->getTraceAsString());
    
    // Send JSON error response
    wp_send_json_error(array(
        'message' => 'An unexpected error occurred during document upload.',
        'debug' => $exception->getMessage()
    ));
}

/**
 * Shutdown handler to catch fatal errors
 */
function aisb_document_upload_shutdown_handler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("AISB_DEBUG: Fatal Error: " . $error['message']);
        error_log("AISB_DEBUG: File: " . $error['file'] . " Line: " . $error['line']);
        
        // Try to send a JSON response if headers haven't been sent
        if (!headers_sent()) {
            wp_send_json_error(array(
                'message' => 'A fatal error occurred during document upload.',
                'debug' => $error['message']
            ));
        }
    }
}

/**
 * Enable debugging for document upload
 */
function aisb_enable_document_upload_debugging() {
    // Set error handlers
    set_error_handler('aisb_document_upload_error_handler');
    set_exception_handler('aisb_document_upload_exception_handler');
    register_shutdown_function('aisb_document_upload_shutdown_handler');
    
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
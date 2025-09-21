<?php
/**
 * Document Upload AJAX Handler
 * 
 * Handles document upload via AJAX (Phase 2 - UI only, no processing)
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Ajax;

use AISB\Document_Processor;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Document Ajax Handler Class
 */
class Document_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_aisb_upload_document', array($this, 'handle_document_upload'));
        add_action('wp_ajax_nopriv_aisb_upload_document', array($this, 'handle_document_upload_nopriv'));
    }
    
    /**
     * Handle document upload for non-logged in users
     */
    public function handle_document_upload_nopriv() {
        wp_send_json_error(array(
            'message' => __('You must be logged in to upload documents.', 'ai-section-builder')
        ));
    }
    
    /**
     * Handle document upload
     */
    public function handle_document_upload() {
        // TEMPORARY: Test if handler is being called
        // Uncomment the next two lines to test if the handler is reached
        // wp_send_json_success(array('message' => 'Handler reached!', 'test' => true));
        // return;
        
        // Load debug helpers
        require_once AISB_PLUGIN_DIR . 'includes/ajax/debug-document-upload.php';
        aisb_enable_document_upload_debugging();
        
        // Enable error reporting for debugging
        error_log('AISB_DEBUG: Document upload handler started');
        error_log('AISB_DEBUG: PHP Version: ' . phpversion());
        error_log('AISB_DEBUG: Memory limit: ' . ini_get('memory_limit'));
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
            error_log('AISB_DEBUG: Nonce verification failed');
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'ai-section-builder')
            ));
            return;
        }
        error_log('AISB_DEBUG: Nonce verified successfully');
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            error_log('AISB_DEBUG: User lacks edit_posts capability');
            wp_send_json_error(array(
                'message' => __('You do not have permission to upload documents.', 'ai-section-builder')
            ));
            return;
        }
        error_log('AISB_DEBUG: User capabilities verified');
        
        // Check if file was uploaded
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            error_log('AISB_DEBUG: File upload error - $_FILES: ' . print_r($_FILES, true));
            wp_send_json_error(array(
                'message' => __('No file uploaded or upload error occurred.', 'ai-section-builder')
            ));
            return;
        }
        error_log('AISB_DEBUG: File received: ' . $_FILES['document']['name']);
        
        $uploaded_file = $_FILES['document'];
        
        // Validate file type
        $allowed_types = array('docx', 'doc', 'txt');
        $file_info = wp_check_filetype($uploaded_file['name']);
        $file_ext = strtolower($file_info['ext']);
        
        if (!in_array($file_ext, $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Invalid file type. Please upload a .docx, .doc, or .txt file.', 'ai-section-builder')
            ));
            return;
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($uploaded_file['size'] > $max_size) {
            wp_send_json_error(array(
                'message' => __('File size exceeds 5MB limit.', 'ai-section-builder')
            ));
            return;
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid post or insufficient permissions.', 'ai-section-builder')
            ));
            return;
        }
        
        // Phase 3: Extract text from document
        
        // Move uploaded file to temporary location
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/aisb-temp/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Generate unique filename
        $temp_filename = uniqid('doc_') . '.' . $file_ext;
        $temp_filepath = $temp_dir . $temp_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($uploaded_file['tmp_name'], $temp_filepath)) {
            wp_send_json_error(array(
                'message' => __('Failed to process uploaded file.', 'ai-section-builder')
            ));
            return;
        }
        
        // Initialize Document Processor
        error_log('AISB_DEBUG: Loading Document Processor');
        require_once AISB_PLUGIN_DIR . 'includes/class-document-processor.php';
        
        if (!class_exists('\AISB\Document_Processor')) {
            error_log('AISB_DEBUG: Document_Processor class not found');
            wp_send_json_error(array(
                'message' => __('Document processor not available.', 'ai-section-builder')
            ));
            return;
        }
        
        $processor = new \AISB\Document_Processor();
        error_log('AISB_DEBUG: Document Processor initialized');
        
        // Extract text from document
        error_log('AISB_DEBUG: Extracting text from: ' . $temp_filepath);
        $result = $processor->extract_text($temp_filepath);
        
        // Clean up temp file
        @unlink($temp_filepath);
        error_log('AISB_DEBUG: Temp file cleaned up');
        
        // Check for errors
        if (is_wp_error($result)) {
            error_log('AISB_DEBUG: Text extraction error: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
            return;
        }
        error_log('AISB_DEBUG: Text extracted successfully, words: ' . $result['stats']['words']);
        
        // Log extraction info for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB Document Extraction: ' . $result['stats']['words'] . ' words extracted from ' . $uploaded_file['name']);
        }
        
        // Phase 4: Process with AI
        error_log('AISB_DEBUG: Starting AI processing');
        try {
            $ai_result = $processor->process_with_ai($result['text'], $post_id);
            error_log('AISB_DEBUG: AI processing completed');
        } catch (\Exception $e) {
            // Catch any exceptions during AI processing
            error_log('AISB_DEBUG: AI Processing Exception: ' . $e->getMessage());
            error_log('AISB_DEBUG: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_success(array(
                'message' => __('Document extracted. AI processing encountered an error.', 'ai-section-builder'),
                'extraction' => array(
                    'preview' => $result['preview'],
                    'stats' => $result['stats']
                ),
                'ai_error' => true,
                'error_message' => $e->getMessage()
            ));
            return;
        } catch (\Error $e) {
            // Catch fatal errors too
            error_log('AISB_DEBUG: AI Processing Fatal Error: ' . $e->getMessage());
            error_log('AISB_DEBUG: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_success(array(
                'message' => __('Document extracted. AI processing encountered a fatal error.', 'ai-section-builder'),
                'extraction' => array(
                    'preview' => $result['preview'],
                    'stats' => $result['stats']
                ),
                'ai_error' => true,
                'error_message' => $e->getMessage()
            ));
            return;
        }
        
        if (is_wp_error($ai_result)) {
            // If AI processing fails, still return the extracted text
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Document extracted but AI processing failed: %s', 'ai-section-builder'),
                    $ai_result->get_error_message()
                ),
                'extraction' => array(
                    'preview' => $result['preview'],
                    'stats' => $result['stats']
                ),
                'ai_error' => true,
                'error_message' => $ai_result->get_error_message()
            ));
            return;
        }
        
        // Store sections in transient for next phase (section generation)
        $transient_key = 'aisb_ai_sections_' . $post_id . '_' . get_current_user_id();
        set_transient($transient_key, $ai_result['sections'], 3600); // Store for 1 hour
        error_log('AISB_DEBUG: Sections stored in transient');
        
        // Return success with AI processing results
        error_log('AISB_DEBUG: Sending success response with ' . $ai_result['sections_count'] . ' sections');
        
        // Debug: Log sections being sent to JavaScript
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($ai_result['sections'])) {
            error_log('AISB_DEBUG: First section being sent: ' . json_encode($ai_result['sections'][0]));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('AI successfully processed document! Generated %d sections from %d words.', 'ai-section-builder'),
                $ai_result['sections_count'],
                $ai_result['word_count']
            ),
            'extraction' => array(
                'preview' => $result['preview'],
                'stats' => $result['stats']
            ),
            'ai_result' => array(
                'summary' => $ai_result['summary'],
                'sections_count' => $ai_result['sections_count'],
                'sections' => $ai_result['sections'], // For preview in UI
                'debug_raw' => isset($ai_result['debug_raw_response']) ? $ai_result['debug_raw_response'] : null
            ),
            'phase_info' => __('Phase 4 Complete: AI processing successful. Sections ready for generation.', 'ai-section-builder')
        ));
    }
}
<?php
/**
 * Document Processor Class
 * 
 * Handles document text extraction and AI processing
 * Phase 4: AI processing implementation
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB;

use AISB\API\AI_Connector;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Document Processor Class
 */
class Document_Processor {
    
    /**
     * Maximum file size in bytes (5MB)
     */
    const MAX_FILE_SIZE = 5242880;
    
    /**
     * Maximum tokens for AI context (approximately 2000 words)
     */
    const MAX_TOKENS = 8000;
    
    /**
     * Extract text from uploaded document
     * 
     * @param string $file_path Path to the uploaded file
     * @return array|WP_Error Extracted text and metadata or error
     */
    public function extract_text($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', __('File not found', 'ai-section-builder'));
        }
        
        // Check file size
        $file_size = filesize($file_path);
        if ($file_size > self::MAX_FILE_SIZE) {
            return new \WP_Error('file_too_large', __('File exceeds 5MB limit', 'ai-section-builder'));
        }
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Extract text based on file type
        switch ($file_ext) {
            case 'docx':
                $text = $this->extract_from_docx($file_path);
                break;
            case 'doc':
                $text = new \WP_Error('unsupported_format', __('.doc files require additional libraries. Please use .docx or .txt format.', 'ai-section-builder'));
                break;
            case 'txt':
                $text = $this->extract_from_txt($file_path);
                break;
            default:
                $text = new \WP_Error('invalid_format', __('Unsupported file format', 'ai-section-builder'));
        }
        
        if (is_wp_error($text)) {
            return $text;
        }
        
        // Clean up the text
        $text = $this->clean_text($text);
        
        // Return extracted text with metadata
        return array(
            'text' => $text,
            'preview' => $this->get_preview($text, 500),
            'stats' => array(
                'characters' => strlen($text),
                'words' => str_word_count($text),
                'paragraphs' => substr_count($text, "\n\n") + 1
            )
        );
    }
    
    /**
     * Extract text from DOCX file
     * 
     * @param string $file_path Path to DOCX file
     * @return string|WP_Error Extracted text or error
     */
    private function extract_from_docx($file_path) {
        // DOCX files are ZIP archives
        $zip = new \ZipArchive();
        
        if ($zip->open($file_path) !== TRUE) {
            return new \WP_Error('zip_error', __('Unable to open DOCX file', 'ai-section-builder'));
        }
        
        // Main document content is in word/document.xml
        $xml_content = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if ($xml_content === false) {
            return new \WP_Error('docx_error', __('Unable to extract content from DOCX file', 'ai-section-builder'));
        }
        
        // Parse XML to extract text
        return $this->parse_docx_xml($xml_content);
    }
    
    /**
     * Parse DOCX XML content
     * 
     * @param string $xml_content XML content from DOCX
     * @return string Extracted text
     */
    private function parse_docx_xml($xml_content) {
        // Load XML
        $dom = new \DOMDocument();
        @$dom->loadXML($xml_content);
        
        if (!$dom) {
            return '';
        }
        
        // Create XPath object
        $xpath = new \DOMXPath($dom);
        
        // Register Word namespace
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        // Extract paragraphs
        $paragraphs = $xpath->query('//w:p');
        $text_content = array();
        
        foreach ($paragraphs as $paragraph) {
            $paragraph_text = '';
            
            // Get all text nodes in this paragraph
            $text_nodes = $xpath->query('.//w:t', $paragraph);
            foreach ($text_nodes as $text_node) {
                $paragraph_text .= $text_node->nodeValue;
            }
            
            // Add non-empty paragraphs
            $paragraph_text = trim($paragraph_text);
            if (!empty($paragraph_text)) {
                $text_content[] = $paragraph_text;
            }
        }
        
        // Join paragraphs with double line breaks
        return implode("\n\n", $text_content);
    }
    
    /**
     * Extract text from TXT file
     * 
     * @param string $file_path Path to TXT file
     * @return string|WP_Error Extracted text or error
     */
    private function extract_from_txt($file_path) {
        $content = file_get_contents($file_path);
        
        if ($content === false) {
            return new \WP_Error('read_error', __('Unable to read text file', 'ai-section-builder'));
        }
        
        // Detect and convert encoding if needed
        $encoding = mb_detect_encoding($content, array('UTF-8', 'ISO-8859-1', 'Windows-1252'), true);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        return $content;
    }
    
    /**
     * Clean extracted text
     * 
     * @param string $text Raw extracted text
     * @return string Cleaned text
     */
    private function clean_text($text) {
        // Remove extra whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalize line breaks
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        
        // Remove excessive line breaks (more than 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Get text preview
     * 
     * @param string $text Full text
     * @param int $length Preview length
     * @return string Preview text
     */
    private function get_preview($text, $length = 500) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        // Cut at word boundary
        $preview = substr($text, 0, $length);
        $last_space = strrpos($preview, ' ');
        
        if ($last_space !== false) {
            $preview = substr($preview, 0, $last_space);
        }
        
        return $preview . '...';
    }
    
    /**
     * Process text with AI to generate sections
     * 
     * @param string $text The extracted document text
     * @param int $post_id The post ID for context
     * @return array|WP_Error Processed sections or error
     */
    public function process_with_ai($text, $post_id = 0) {
        // Check if AI settings are configured
        $settings = get_option('aisb_ai_settings');
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB AI Settings: ' . print_r($settings, true));
        }
        
        if (empty($settings) || empty($settings['provider'])) {
            return new \WP_Error('ai_not_configured', __('AI provider not configured. Please configure AI settings first.', 'ai-section-builder'));
        }
        
        // Truncate text if too long (to stay within token limits)
        $truncated_text = $this->truncate_for_ai($text);
        
        // Generate the AI prompt
        $prompt = $this->generate_ai_prompt($truncated_text);
        
        // Call the appropriate AI provider
        $response = null;
        switch ($settings['provider']) {
            case 'openai':
                $response = $this->call_openai_api($prompt, $settings);
                break;
            case 'anthropic':
                $response = $this->call_anthropic_api($prompt, $settings);
                break;
            default:
                return new \WP_Error('invalid_provider', __('Invalid AI provider selected.', 'ai-section-builder'));
        }
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Debug: Log raw AI response
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB_DEBUG: Raw AI Response: ' . substr($response, 0, 1000));
        }
        
        // Parse AI response into sections
        $sections = $this->parse_ai_response($response);
        
        if (is_wp_error($sections)) {
            error_log('AISB_DEBUG: Parse error: ' . $sections->get_error_message());
            return $sections;
        }
        
        // Debug: Log parsed sections
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB_DEBUG: Parsed sections count: ' . count($sections));
            error_log('AISB_DEBUG: Sections structure: ' . json_encode($sections));
        }
        
        return array(
            'sections' => $sections,
            'summary' => $this->generate_summary($sections),
            'word_count' => str_word_count($text),
            'sections_count' => count($sections),
            'debug_raw_response' => defined('WP_DEBUG') && WP_DEBUG ? $response : null
        );
    }
    
    /**
     * Truncate text for AI processing
     * 
     * @param string $text Full text
     * @return string Truncated text
     */
    private function truncate_for_ai($text) {
        // Estimate 4 characters per token (conservative)
        $max_chars = self::MAX_TOKENS * 4;
        
        if (strlen($text) <= $max_chars) {
            return $text;
        }
        
        // Cut at paragraph boundary if possible
        $truncated = substr($text, 0, $max_chars);
        $last_paragraph = strrpos($truncated, "\n\n");
        
        if ($last_paragraph !== false && $last_paragraph > $max_chars * 0.8) {
            $truncated = substr($truncated, 0, $last_paragraph);
        }
        
        return $truncated;
    }
    
    /**
     * Generate AI prompt for section creation
     * 
     * @param string $text Document text
     * @return string AI prompt
     */
    private function generate_ai_prompt($text) {
        $prompt = "You are an expert content strategist. Analyze the following document and create website sections from it.

Available section types with EXACT field requirements:
1. hero - Main intro section
   Fields: heading (string), subheading (string), description (string), buttons (array)
   
2. hero-form - Hero with contact form
   Fields: heading (string), subheading (string), description (string), buttons (array)
   
3. features - Feature showcase
   Fields: heading (string), features (array of objects with: title, description)
   
4. checklist - Benefits/items list
   Fields: heading (string), items (array of strings or objects with: text, description)
   
5. faq - Questions and answers
   Fields: heading (string), faqs (array of objects with: question, answer)
   
6. stats - Numbers/metrics
   Fields: heading (string), stats (array of objects with: number, label, description)
   
7. testimonials - Customer reviews
   Fields: heading (string), testimonials (array of objects with: author, content, rating)

Document content:
---
$text
---

Analyze this content and create sections. IMPORTANT:
- Extract ACTUAL content from the document, not placeholders
- For testimonials, extract the FULL quote text within the quotation marks
- For stats, keep symbols with numbers (e.g., \"99%\" not just \"99\")
- Include ALL section types found in the document

Return JSON with these EXACT structures for each section type:
{
  \"sections\": [
    {
      \"type\": \"hero\",
      \"heading\": \"[Main title - usually the company/document name]\",
      \"subheading\": \"[Tagline or subtitle if present]\",
      \"description\": \"[Main introductory paragraph]\",
      \"buttons\": [
        {\"text\": \"Get Started\", \"url\": \"#contact\", \"style\": \"primary\"}
      ]
    },
    {
      \"type\": \"features\",
      \"heading\": \"[Section heading like 'Our Services' or 'Practice Areas']\",
      \"features\": [
        {
          \"title\": \"[Feature/service name]\",
          \"description\": \"[Full description paragraph]\"
        }
      ]
    },
    {
      \"type\": \"checklist\",
      \"heading\": \"[Section heading like 'Why Choose Us']\",
      \"items\": [\"[Each bullet point as a string]\"]
    },
    {
      \"type\": \"faq\",
      \"heading\": \"Frequently Asked Questions\",
      \"faqs\": [
        {
          \"question\": \"[The question text]\",
          \"answer\": \"[The complete answer text]\"
        }
      ]
    },
    {
      \"type\": \"testimonials\",
      \"heading\": \"What Our Clients Say\",
      \"testimonials\": [
        {
          \"quote\": \"[FULL testimonial text from within quotation marks]\",
          \"author\": \"[Client name and title/description]\",
          \"rating\": 5
        }
      ]
    },
    {
      \"type\": \"stats\",
      \"heading\": \"[Section heading like 'Proven Results']\",
      \"stats\": [
        {
          \"number\": \"[Number WITH symbol e.g. '99%' or '$50M+']\",
          \"label\": \"[Description like 'Client Satisfaction']\",
          \"description\": \"\"
        }
      ]
    },
    {
      \"type\": \"hero-form\",
      \"heading\": \"Contact Us\",
      \"subheading\": \"[Call to action text]\",
      \"description\": \"[Contact description]\",
      \"buttons\": [
        {\"text\": \"Call (555) 123-4567\", \"url\": \"tel:5551234567\", \"style\": \"primary\"},
        {\"text\": \"Email Us\", \"url\": \"mailto:info@example.com\", \"style\": \"secondary\"}
      ]
    }
  ]
}

Return ONLY valid JSON, no explanations.";
        
        return $prompt;
    }
    
    /**
     * Call OpenAI API
     * 
     * @param string $prompt The prompt
     * @param array $settings AI settings
     * @return string|WP_Error Response or error
     */
    private function call_openai_api($prompt, $settings) {
        // Use AI_Connector to get the encrypted API key
        require_once AISB_PLUGIN_DIR . 'includes/api/class-ai-connector.php';
        
        // Get and decrypt the API key
        if (isset($settings['openai_api_key'])) {
            $api_key = \AISB\API\AI_Connector::decrypt_api_key($settings['openai_api_key']);
        } else {
            return new \WP_Error('no_api_key', __('OpenAI API key not configured.', 'ai-section-builder'));
        }
        
        if (!$api_key) {
            return new \WP_Error('invalid_api_key', __('Failed to decrypt API key.', 'ai-section-builder'));
        }
        
        $model = isset($settings['openai_model']) ? $settings['openai_model'] : 'gpt-3.5-turbo';
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that creates website sections from document content. Always respond with valid JSON only.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 2000
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new \WP_Error('openai_error', $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new \WP_Error('invalid_response', __('Invalid response from OpenAI.', 'ai-section-builder'));
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * Call Anthropic API
     * 
     * @param string $prompt The prompt
     * @param array $settings AI settings
     * @return string|WP_Error Response or error
     */
    private function call_anthropic_api($prompt, $settings) {
        // Use AI_Connector to get the encrypted API key
        require_once AISB_PLUGIN_DIR . 'includes/api/class-ai-connector.php';
        
        // Get and decrypt the API key
        if (isset($settings['anthropic_api_key'])) {
            $api_key = \AISB\API\AI_Connector::decrypt_api_key($settings['anthropic_api_key']);
        } else {
            return new \WP_Error('no_api_key', __('Anthropic API key not configured.', 'ai-section-builder'));
        }
        
        if (!$api_key) {
            return new \WP_Error('invalid_api_key', __('Failed to decrypt API key.', 'ai-section-builder'));
        }
        
        $model = isset($settings['anthropic_model']) ? $settings['anthropic_model'] : 'claude-3-sonnet-20240229';
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => 2000,
                'temperature' => 0.7
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new \WP_Error('anthropic_error', $data['error']['message']);
        }
        
        if (!isset($data['content'][0]['text'])) {
            return new \WP_Error('invalid_response', __('Invalid response from Anthropic.', 'ai-section-builder'));
        }
        
        return $data['content'][0]['text'];
    }
    
    /**
     * Parse AI response into sections
     * 
     * @param string $response AI response
     * @return array|WP_Error Parsed sections or error
     */
    private function parse_ai_response($response) {
        // Try to extract JSON from the response
        $json_match = null;
        if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
            $json_match = $matches[0];
        } else {
            $json_match = $response;
        }
        
        $data = json_decode($json_match, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_parse_error', __('Failed to parse AI response as JSON.', 'ai-section-builder'));
        }
        
        if (!isset($data['sections']) || !is_array($data['sections'])) {
            return new \WP_Error('invalid_format', __('AI response does not contain valid sections.', 'ai-section-builder'));
        }
        
        // Validate and sanitize sections
        $validated_sections = array();
        foreach ($data['sections'] as $section) {
            $validated_section = $this->validate_section($section);
            if ($validated_section) {
                $validated_sections[] = $validated_section;
            }
        }
        
        if (empty($validated_sections)) {
            return new \WP_Error('no_valid_sections', __('No valid sections could be created from the AI response.', 'ai-section-builder'));
        }
        
        return $validated_sections;
    }
    
    /**
     * Validate and sanitize a section
     * 
     * @param array $section Section data
     * @return array|null Validated section or null if invalid
     */
    private function validate_section($section) {
        if (!isset($section['type'])) {
            return null;
        }
        
        $allowed_types = array('hero', 'hero-form', 'features', 'checklist', 'faq', 'stats', 'testimonials');
        if (!in_array($section['type'], $allowed_types)) {
            return null;
        }
        
        // Sanitize common fields
        $validated = array(
            'type' => sanitize_key($section['type'])
        );
        
        // Type-specific validation
        switch ($section['type']) {
            case 'hero':
            case 'hero-form':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['subheading'] = isset($section['subheading']) ? sanitize_text_field($section['subheading']) : '';
                $validated['description'] = isset($section['description']) ? wp_kses_post($section['description']) : '';
                $validated['buttons'] = isset($section['buttons']) ? $this->validate_buttons($section['buttons']) : array();
                break;
                
            case 'features':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['features'] = isset($section['features']) ? $this->validate_features($section['features']) : array();
                break;
                
            case 'checklist':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['items'] = isset($section['items']) ? $this->validate_checklist_items($section['items']) : array();
                break;
                
            case 'faq':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['faqs'] = isset($section['faqs']) ? $this->validate_faqs($section['faqs']) : array();
                break;
                
            case 'stats':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['stats'] = isset($section['stats']) ? $this->validate_stats($section['stats']) : array();
                break;
                
            case 'testimonials':
                $validated['heading'] = isset($section['heading']) ? sanitize_text_field($section['heading']) : '';
                $validated['testimonials'] = isset($section['testimonials']) ? $this->validate_testimonials($section['testimonials']) : array();
                break;
        }
        
        return $validated;
    }
    
    /**
     * Validate buttons
     */
    private function validate_buttons($buttons) {
        if (!is_array($buttons)) return array();
        
        $validated = array();
        foreach ($buttons as $button) {
            if (isset($button['text'])) {
                $validated[] = array(
                    'text' => sanitize_text_field($button['text']),
                    'url' => isset($button['url']) ? esc_url($button['url']) : '#',
                    'style' => isset($button['style']) ? sanitize_key($button['style']) : 'primary'
                );
            }
        }
        return $validated;
    }
    
    /**
     * Validate features
     */
    private function validate_features($features) {
        if (!is_array($features)) return array();
        
        $validated = array();
        foreach ($features as $feature) {
            $validated[] = array(
                'icon' => isset($feature['icon']) ? sanitize_text_field($feature['icon']) : 'dashicons-yes',
                'title' => isset($feature['title']) ? sanitize_text_field($feature['title']) : '',
                'description' => isset($feature['description']) ? wp_kses_post($feature['description']) : ''
            );
        }
        return array_slice($validated, 0, 12); // Limit to 12 features
    }
    
    /**
     * Validate checklist items
     */
    private function validate_checklist_items($items) {
        if (!is_array($items)) return array();
        
        $validated = array();
        foreach ($items as $item) {
            $text = is_string($item) ? $item : (isset($item['text']) ? $item['text'] : '');
            if ($text) {
                $validated[] = sanitize_text_field($text);
            }
        }
        return array_slice($validated, 0, 20); // Limit to 20 items
    }
    
    /**
     * Validate FAQs
     */
    private function validate_faqs($faqs) {
        if (!is_array($faqs)) return array();
        
        $validated = array();
        foreach ($faqs as $faq) {
            if (isset($faq['question']) && isset($faq['answer'])) {
                $validated[] = array(
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => wp_kses_post($faq['answer'])
                );
            }
        }
        return array_slice($validated, 0, 15); // Limit to 15 FAQs
    }
    
    /**
     * Validate stats
     */
    private function validate_stats($stats) {
        if (!is_array($stats)) return array();
        
        $validated = array();
        foreach ($stats as $stat) {
            $validated[] = array(
                'number' => isset($stat['number']) ? sanitize_text_field($stat['number']) : '0',
                'label' => isset($stat['label']) ? sanitize_text_field($stat['label']) : '',
                'description' => isset($stat['description']) ? wp_kses_post($stat['description']) : ''
            );
        }
        return array_slice($validated, 0, 6); // Limit to 6 stats
    }
    
    /**
     * Validate testimonials
     */
    private function validate_testimonials($testimonials) {
        if (!is_array($testimonials)) return array();
        
        $validated = array();
        foreach ($testimonials as $testimonial) {
            $validated[] = array(
                'quote' => isset($testimonial['quote']) ? wp_kses_post($testimonial['quote']) : '',
                'author' => isset($testimonial['author']) ? sanitize_text_field($testimonial['author']) : '',
                'role' => isset($testimonial['role']) ? sanitize_text_field($testimonial['role']) : '',
                'rating' => isset($testimonial['rating']) ? intval($testimonial['rating']) : 5
            );
        }
        return array_slice($validated, 0, 10); // Limit to 10 testimonials
    }
    
    /**
     * Generate summary of sections
     * 
     * @param array $sections Validated sections
     * @return string Summary
     */
    private function generate_summary($sections) {
        $types = array_column($sections, 'type');
        $type_counts = array_count_values($types);
        
        $summary_parts = array();
        foreach ($type_counts as $type => $count) {
            $type_label = ucfirst(str_replace('-', ' ', $type));
            $summary_parts[] = sprintf('%d %s', $count, $type_label);
        }
        
        return implode(', ', $summary_parts);
    }
}
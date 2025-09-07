<?php
/**
 * Debug test file to check saved data
 * Load this file directly to see what's saved in the database
 * 
 * Usage: Navigate to /wp-content/plugins/ai_section_builder_v2/debug-test.php?post_id=YOUR_POST_ID
 */

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id) {
    die('Please provide a post_id parameter');
}

// Get the saved sections
$sections = get_post_meta($post_id, '_aisb_sections', true);

echo "<h1>AISB Debug for Post ID: $post_id</h1>";
echo "<pre>";
echo "Raw Sections Data:\n";
print_r($sections);
echo "\n\n";

if (is_array($sections) && !empty($sections)) {
    foreach ($sections as $index => $section) {
        echo "Section $index:\n";
        echo "Type: " . ($section['type'] ?? 'unknown') . "\n";
        
        if (isset($section['content'])) {
            $content = $section['content'];
            echo "Content fields:\n";
            echo "  - media_type: " . ($content['media_type'] ?? 'NOT SET') . "\n";
            echo "  - featured_image: " . ($content['featured_image'] ?? 'NOT SET') . "\n";
            echo "  - video_url: " . ($content['video_url'] ?? 'NOT SET') . "\n";
            echo "  - heading: " . ($content['heading'] ?? 'NOT SET') . "\n";
            echo "\n";
        }
    }
}

echo "</pre>";

// Also check if the plugin is active for this post
$is_active = get_post_meta($post_id, '_aisb_active', true);
echo "<p>Plugin active for this post: " . ($is_active ? 'YES' : 'NO') . "</p>";
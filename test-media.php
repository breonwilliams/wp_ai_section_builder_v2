<?php
/**
 * Test file to verify media fields are preserved in migration
 * Usage: Navigate to /wp-content/plugins/ai_section_builder_v2/test-media.php
 */

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Test data with media fields
$test_content = array(
    'media_type' => 'image',
    'featured_image' => 'https://example.com/test-image.jpg',
    'video_url' => 'https://www.youtube.com/watch?v=test123',
    'heading' => 'Test Heading',
    'content' => '<p>Test content paragraph</p>',
    'eyebrow_heading' => 'Test Eyebrow',
    'theme_variant' => 'dark',
    'layout_variant' => 'content-left'
);

echo "<h1>Media Field Migration Test</h1>";
echo "<pre>";
echo "Original content:\n";
print_r($test_content);
echo "\n";

// Run the migration function
$migrated = aisb_migrate_field_names($test_content);

echo "Migrated content:\n";
print_r($migrated);
echo "\n";

// Check specific fields
echo "Test Results:\n";
echo "=============\n";
echo "✓ media_type preserved: " . (isset($migrated['media_type']) ? 'YES (value: ' . $migrated['media_type'] . ')' : 'NO - FAILED!') . "\n";
echo "✓ featured_image preserved: " . (isset($migrated['featured_image']) ? 'YES' : 'NO - FAILED!') . "\n";
echo "✓ video_url preserved: " . (isset($migrated['video_url']) ? 'YES' : 'NO - FAILED!') . "\n";
echo "✓ heading preserved: " . (isset($migrated['heading']) ? 'YES' : 'NO - FAILED!') . "\n";
echo "✓ content preserved: " . (isset($migrated['content']) ? 'YES' : 'NO - FAILED!') . "\n";
echo "✓ theme_variant preserved: " . (isset($migrated['theme_variant']) ? 'YES' : 'NO - FAILED!') . "\n";
echo "✓ layout_variant preserved: " . (isset($migrated['layout_variant']) ? 'YES' : 'NO - FAILED!') . "\n";

echo "\n";
echo "Old fields that should be removed:\n";
echo "✓ media_image_url removed: " . (!isset($migrated['media_image_url']) ? 'YES' : 'NO - Still present!') . "\n";
echo "✓ media_video_type removed: " . (!isset($migrated['media_video_type']) ? 'YES' : 'NO - Still present!') . "\n";

echo "</pre>";
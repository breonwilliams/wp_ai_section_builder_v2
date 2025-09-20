<?php
/**
 * Verify AI Settings Functionality
 * 
 * This script tests that AI settings are working correctly after the fix
 * Usage: wp eval-file wp-content/plugins/ai_section_builder_v2/tests/verify-settings.php
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

echo "\n=====================================\n";
echo "AI Settings Verification Test\n";
echo "=====================================\n\n";

// 1. Check current settings
echo "1. Current Settings Status:\n";
$settings = get_option('aisb_ai_settings', array());

if (empty($settings)) {
    echo "   ⚠️  No settings found - Please configure in WordPress admin\n";
} else {
    echo "   ✅ Settings found in database\n";
    echo "   - Provider: " . (isset($settings['provider']) ? $settings['provider'] : 'not set') . "\n";
    echo "   - Has API Key: " . (!empty($settings['api_key']) ? 'Yes' : 'No') . "\n";
    echo "   - Model: " . (isset($settings['model']) ? $settings['model'] : 'not set') . "\n";
    echo "   - Verified: " . (isset($settings['verified']) ? ($settings['verified'] ? 'Yes' : 'No') : 'not set') . "\n";
    
    if (isset($settings['last_verified']) && $settings['last_verified'] > 0) {
        echo "   - Last Verified: " . date('Y-m-d H:i:s', $settings['last_verified']) . "\n";
    }
}

echo "\n";

// 2. Test encryption functions
echo "2. Encryption Functions:\n";
if (function_exists('aisb_encrypt_api_key') && function_exists('aisb_decrypt_api_key')) {
    echo "   ✅ Encryption functions available\n";
    
    // Test encryption/decryption
    $test_key = 'test-key-12345';
    $encrypted = aisb_encrypt_api_key($test_key);
    $decrypted = aisb_decrypt_api_key($encrypted);
    
    if ($decrypted === $test_key) {
        echo "   ✅ Encryption/decryption working correctly\n";
    } else {
        echo "   ❌ Encryption/decryption mismatch\n";
    }
} else {
    echo "   ❌ Encryption functions not found\n";
}

echo "\n";

// 3. Test settings persistence
echo "3. Settings Persistence Test:\n";
if (!empty($settings['api_key'])) {
    // Save current settings back (simulating the save process)
    $test_save = update_option('aisb_ai_settings', $settings);
    
    if ($test_save !== false) {
        // Reload settings
        $reloaded = get_option('aisb_ai_settings', array());
        
        if (!empty($reloaded['api_key']) && $reloaded['api_key'] === $settings['api_key']) {
            echo "   ✅ Settings persist correctly\n";
        } else {
            echo "   ❌ Settings not persisting\n";
        }
    } else {
        echo "   ⚠️  No changes to test (settings unchanged)\n";
    }
} else {
    echo "   ⚠️  No API key to test persistence\n";
}

echo "\n";

// 4. Provider compatibility check
echo "4. Provider Compatibility:\n";
$supported_providers = ['openai', 'anthropic'];
if (isset($settings['provider'])) {
    if (in_array($settings['provider'], $supported_providers)) {
        echo "   ✅ Provider '{$settings['provider']}' is supported\n";
        
        // Check if model matches provider
        if (isset($settings['model'])) {
            $openai_models = ['gpt-4', 'gpt-4-turbo-preview', 'gpt-3.5-turbo'];
            $anthropic_models = ['claude-3-opus-20240229', 'claude-3-5-sonnet-20241022', 'claude-3-5-sonnet-20240620', 'claude-3-haiku-20240307'];
            
            if ($settings['provider'] === 'openai' && in_array($settings['model'], $openai_models)) {
                echo "   ✅ Model '{$settings['model']}' is valid for OpenAI\n";
            } elseif ($settings['provider'] === 'anthropic' && in_array($settings['model'], $anthropic_models)) {
                echo "   ✅ Model '{$settings['model']}' is valid for Anthropic\n";
            } else {
                echo "   ⚠️  Model may not match provider\n";
            }
        }
    } else {
        echo "   ❌ Provider '{$settings['provider']}' not recognized\n";
    }
} else {
    echo "   ⚠️  No provider selected\n";
}

echo "\n=====================================\n";
echo "Test Complete\n";
echo "=====================================\n\n";

// Testing instructions
echo "Manual Testing Steps:\n";
echo "1. Go to WordPress Admin → AI Section Builder → AI Settings\n";
echo "2. Select your AI provider (OpenAI or Anthropic)\n";
echo "3. Enter your API key\n";
echo "4. Select a model\n";
echo "5. Click 'Save Settings'\n";
echo "6. Navigate away from the page\n";
echo "7. Return to AI Settings\n";
echo "8. ✅ Your settings should still be there!\n";
echo "9. Click 'Test Connection' to verify API key works\n\n";

// Summary
if (!empty($settings['api_key']) && isset($settings['provider'])) {
    echo "🎉 Your settings appear to be configured correctly!\n";
} else {
    echo "⚠️  Please complete the AI Settings configuration.\n";
}
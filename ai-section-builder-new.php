<?php
/**
 * Plugin Name: AI Section Builder Pro
 * Plugin URI: https://github.com/breonwilliams/ai-section-builder
 * Description: AI-powered section builder for WordPress. Build beautiful pages with pre-designed sections or generate content from documents using AI.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Breon Williams
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-section-builder
 * Domain Path: /languages
 * 
 * @package AISB
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load autoloader
require_once __DIR__ . '/src/autoload.php';

// Use namespaced classes
use AISB\Core\Plugin;

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::getInstance()->init();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    Plugin::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    Plugin::deactivate();
});
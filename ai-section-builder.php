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
 * Network: false
 * 
 * @package AISectiionBuilder
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent double initialization
if (defined('AISB_INITIALIZED')) {
    return;
}
define('AISB_INITIALIZED', true);

// Plugin constants - using PHP native functions to avoid WordPress dependency
define('AISB_VERSION', '2.0.0');
define('AISB_PLUGIN_FILE', __FILE__);

// Use PHP's dirname instead of plugin_dir_path to avoid WordPress function dependency
define('AISB_PLUGIN_DIR', dirname(__FILE__) . '/');

// Calculate plugin basename without WordPress functions
$plugin_folder = basename(dirname(__FILE__));
$plugin_file = basename(__FILE__);
define('AISB_PLUGIN_BASENAME', $plugin_folder . '/' . $plugin_file);

// Plugin URL needs special handling since we can't use plugin_dir_url() here
// We'll set a temporary value and update it once WordPress is loaded
if (defined('WP_PLUGIN_URL')) {
    define('AISB_PLUGIN_URL', WP_PLUGIN_URL . '/' . $plugin_folder . '/');
} elseif (defined('WP_CONTENT_URL')) {
    define('AISB_PLUGIN_URL', WP_CONTENT_URL . '/plugins/' . $plugin_folder . '/');
} else {
    // Fallback: we'll correct this later when WordPress is fully loaded
    define('AISB_PLUGIN_URL_TEMP', true);
    define('AISB_PLUGIN_URL', '/wp-content/plugins/' . $plugin_folder . '/');
}

// Encryption constants - should be defined in wp-config.php for security
// These use WordPress constants that are defined early in wp-config.php
// or fall back to deterministic values based on the WordPress installation
if (!defined('AISB_ENCRYPTION_KEY')) {
    // Try to use WordPress security key if available
    if (defined('SECURE_AUTH_KEY') && SECURE_AUTH_KEY !== 'put your unique phrase here') {
        define('AISB_ENCRYPTION_KEY', SECURE_AUTH_KEY);
    } elseif (defined('AUTH_KEY') && AUTH_KEY !== 'put your unique phrase here') {
        // Fallback to AUTH_KEY if SECURE_AUTH_KEY not customized
        define('AISB_ENCRYPTION_KEY', AUTH_KEY);
    } else {
        // Last resort: use a deterministic key based on site path
        // This is less secure but won't break the site
        define('AISB_ENCRYPTION_KEY', hash('sha256', ABSPATH . 'aisb-encryption-key'));
    }
}

if (!defined('AISB_ENCRYPTION_SALT')) {
    // Try to use WordPress security salt if available
    if (defined('SECURE_AUTH_SALT') && SECURE_AUTH_SALT !== 'put your unique phrase here') {
        define('AISB_ENCRYPTION_SALT', substr(SECURE_AUTH_SALT, 0, 16));
    } elseif (defined('AUTH_SALT') && AUTH_SALT !== 'put your unique phrase here') {
        // Fallback to AUTH_SALT if SECURE_AUTH_SALT not customized
        define('AISB_ENCRYPTION_SALT', substr(AUTH_SALT, 0, 16));
    } else {
        // Last resort: use a deterministic salt based on site path
        // This is less secure but won't break the site
        define('AISB_ENCRYPTION_SALT', substr(hash('sha256', ABSPATH . 'aisb-salt'), 0, 16));
    }
}

// Debug logging for troubleshooting
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function() {
        $plugin_url = function_exists('aisb_plugin_url') ? aisb_plugin_url() : AISB_PLUGIN_URL;
        error_log('===== AISB Debug Info =====');
        error_log('AISB_PLUGIN_DIR: ' . AISB_PLUGIN_DIR);
        error_log('AISB_PLUGIN_URL (raw): ' . AISB_PLUGIN_URL);
        error_log('AISB_PLUGIN_URL (via helper): ' . $plugin_url);
        error_log('AISB_PLUGIN_BASENAME: ' . AISB_PLUGIN_BASENAME);
        error_log('Editor.js path: ' . $plugin_url . 'assets/js/editor/editor.js');
        error_log('Editor.js file exists: ' . (file_exists(AISB_PLUGIN_DIR . 'assets/js/editor/editor.js') ? 'YES' : 'NO'));
        if (defined('AISB_PLUGIN_URL_TEMP')) {
            error_log('URL was set with fallback, needs correction');
        }
        if (defined('AISB_PLUGIN_URL_CORRECTED')) {
            error_log('Corrected URL: ' . AISB_PLUGIN_URL_CORRECTED);
        }
        error_log('===========================');
    }, 1);
}

// Load autoloader
require_once AISB_PLUGIN_DIR . 'includes/class-aisb-loader.php';
AISB_Loader::init(AISB_PLUGIN_DIR);
AISB_Loader::load_section_classes();

// Load backward compatibility wrappers
require_once AISB_PLUGIN_DIR . 'includes/section-wrappers.php';
require_once AISB_PLUGIN_DIR . 'includes/admin-wrappers.php';
require_once AISB_PLUGIN_DIR . 'includes/api-wrappers.php';
require_once AISB_PLUGIN_DIR . 'includes/core-wrappers.php';

// Initialize core components
add_action('init', function() {
    // Initialize core classes
    require_once AISB_PLUGIN_DIR . 'includes/core/class-template-handler.php';
    require_once AISB_PLUGIN_DIR . 'includes/core/class-asset-manager.php';
    require_once AISB_PLUGIN_DIR . 'includes/core/class-conflict-detector.php';
    require_once AISB_PLUGIN_DIR . 'includes/core/class-editor-manager.php';
    require_once AISB_PLUGIN_DIR . 'includes/core/class-utilities.php';
    
    // Create instances
    new \AISB\Core\Template_Handler();
    new \AISB\Core\Asset_Manager();
    new \AISB\Core\Conflict_Detector();
    new \AISB\Core\Editor_Manager();
    new \AISB\Core\Utilities();
    
    // Initialize API components (needed for both admin and frontend)
    require_once AISB_PLUGIN_DIR . 'includes/api/class-rest-api.php';
    new \AISB\API\REST_API();
    
    // Initialize admin components
    if (is_admin()) {
        // Load admin classes
        require_once AISB_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
        require_once AISB_PLUGIN_DIR . 'includes/admin/class-admin-dashboard.php';
        require_once AISB_PLUGIN_DIR . 'includes/admin/class-ai-settings.php';
        require_once AISB_PLUGIN_DIR . 'includes/admin/class-meta-boxes.php';
        require_once AISB_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';
        require_once AISB_PLUGIN_DIR . 'includes/ajax/class-editor-ajax.php';
        require_once AISB_PLUGIN_DIR . 'includes/ajax/class-ai-ajax.php';
        
        // Initialize admin components
        new \AISB\Admin\Admin_Menu();
        new \AISB\Admin\Meta_Boxes();
        
        // Initialize AJAX handlers
        new \AISB\Ajax\Editor_Ajax();
        new \AISB\Ajax\AI_Ajax();
    }
});

// Initialize plugin
add_action('plugins_loaded', 'aisb_init');

function aisb_init() {
    // Safety check: Ensure WordPress functions are available
    if (!function_exists('add_action') || !function_exists('get_option')) {
        return;
    }
    
    // Fix plugin URL if it was set with fallback
    if (defined('AISB_PLUGIN_URL_TEMP') && function_exists('plugin_dir_url')) {
        $correct_url = plugin_dir_url(AISB_PLUGIN_FILE);
        if (!defined('AISB_PLUGIN_URL_CORRECTED')) {
            define('AISB_PLUGIN_URL_CORRECTED', $correct_url);
        }
    }
    
    // Load text domain for translations
    load_plugin_textdomain('ai-section-builder', false, dirname(AISB_PLUGIN_BASENAME) . '/languages');
    
    // Run migration to clean up old data
    aisb_migrate_cleanup_old_data();
    
    // Initialize plugin setup
    aisb_setup();
}

/**
 * Helper function to get the correct plugin URL
 * This ensures we always get the right URL even if it was set with a fallback
 */
function aisb_plugin_url() {
    if (defined('AISB_PLUGIN_URL_CORRECTED')) {
        return AISB_PLUGIN_URL_CORRECTED;
    }
    if (function_exists('plugin_dir_url')) {
        return plugin_dir_url(AISB_PLUGIN_FILE);
    }
    return AISB_PLUGIN_URL;
}

function aisb_setup() {
    // Load Color Settings class
    require_once AISB_PLUGIN_DIR . 'includes/class-aisb-color-settings.php';
    
    // Hook into WordPress
    add_action('init', 'aisb_register_post_type');
}

/**
 * Register custom post type for sections library (future use)
 */
function aisb_register_post_type() {
    // We'll add this later when needed
    // For now, we'll just use post meta
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'aisb_activate');

function aisb_activate() {
    // Set default options
    add_option('aisb_version', AISB_VERSION);
    
    // Future: Create database tables if needed for advanced features
}

/**
 * Deactivation hook  
 */
register_deactivation_hook(__FILE__, 'aisb_deactivate');

function aisb_deactivate() {
    // Clean up if needed
}
<?php
/**
 * Admin Function Wrappers for Backward Compatibility
 *
 * These wrapper functions maintain backward compatibility for admin functions
 * that have been moved to classes.
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render admin page - wrapper for backward compatibility
 */
function aisb_admin_page() {
    $dashboard = new \AISB\Admin\Admin_Dashboard();
    $dashboard->render();
}

/**
 * Render AI settings page - wrapper for backward compatibility
 */
function aisb_render_ai_settings_page() {
    $settings = new \AISB\Admin\AI_Settings();
    $settings->render();
}
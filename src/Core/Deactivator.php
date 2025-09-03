<?php
/**
 * Plugin Deactivation Handler
 *
 * @package AISB\Core
 * @since 2.0.0
 */

namespace AISB\Core;

/**
 * Handles plugin deactivation
 */
class Deactivator {
    /**
     * Deactivate the plugin
     */
    public function deactivate() {
        // Clear scheduled events
        $this->clear_scheduled_events();
        
        // Clear transients
        $this->clear_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear scheduled cron events
     */
    private function clear_scheduled_events() {
        wp_clear_scheduled_hook('aisb_cleanup_transients');
    }

    /**
     * Clear plugin transients
     */
    private function clear_transients() {
        global $wpdb;
        
        // Delete all AISB transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aisb_%' 
             OR option_name LIKE '_transient_timeout_aisb_%'"
        );
        
        // Clear object cache
        wp_cache_flush();
    }
}
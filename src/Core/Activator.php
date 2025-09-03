<?php
/**
 * Plugin Activation Handler
 *
 * @package AISB\Core
 * @since 2.0.0
 */

namespace AISB\Core;

use AISB\Database\Schema;

/**
 * Handles plugin activation
 */
class Activator {
    /**
     * Activate the plugin
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Add database indexes for performance
        $this->add_indexes();
        
        // Set activation timestamp
        update_option('aisb_activated', current_time('timestamp'));
        
        // Set plugin version
        update_option('aisb_version', AISB_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cron events if needed
        $this->schedule_events();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        // Future: Create custom tables if needed
        // For now, we're using post meta
    }

    /**
     * Add database indexes for performance
     */
    private function add_indexes() {
        global $wpdb;
        
        // Add indexes for our meta keys to improve query performance
        $indexes = [
            '_aisb_enabled',
            '_aisb_sections',
            '_aisb_original_content',
            '_aisb_switched_from'
        ];
        
        foreach ($indexes as $meta_key) {
            // Check if index exists before adding
            $index_name = 'idx_aisb_' . str_replace('_', '', $meta_key);
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = %s",
                $index_name
            ));
            
            if (!$index_exists) {
                $wpdb->query(
                    "ALTER TABLE {$wpdb->postmeta} 
                     ADD INDEX {$index_name} (meta_key(20), post_id)"
                );
            }
        }
    }

    /**
     * Schedule cron events
     */
    private function schedule_events() {
        // Schedule cleanup task if needed
        if (!wp_next_scheduled('aisb_cleanup_transients')) {
            wp_schedule_event(time(), 'daily', 'aisb_cleanup_transients');
        }
    }
}
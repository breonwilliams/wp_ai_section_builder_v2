<?php
/**
 * Utilities Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * General utility functions
 */
class Utilities {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Performance optimizations
        add_action('save_post', array($this, 'clear_cache_on_save'), 20);
        add_action('delete_post', array($this, 'clear_cache_on_delete'));
        
        // REST API endpoint for page/post search
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Migrate old field names to new structure
     *
     * @param array $content Section content
     * @return array Updated content
     */
    public static function migrate_field_names($content) {
        if (!is_array($content)) {
            return $content;
        }
        
        // Map old field names to new
        $field_map = [
            'heading' => 'headline',
            'subheading' => 'subheadline',
            'button_text' => 'cta_text',
            'button_url' => 'cta_url',
            'background' => 'bg_color'
        ];
        
        foreach ($field_map as $old => $new) {
            if (isset($content[$old]) && !isset($content[$new])) {
                $content[$new] = $content[$old];
                unset($content[$old]);
            }
        }
        
        return $content;
    }
    
    /**
     * Migrate and cleanup old Phase 1A data
     */
    public static function migrate_cleanup_old_data() {
        global $wpdb;
        
        // Check if migration already done
        $migration_done = get_option('aisb_phase1b_migration', false);
        if ($migration_done) {
            return;
        }
        
        // Clean up old post meta from Phase 1A
        $posts_with_old_meta = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key IN ('_aisb_phase1_enabled', '_aisb_phase1_sections')
        ");
        
        foreach ($posts_with_old_meta as $row) {
            // Remove old meta
            delete_post_meta($row->post_id, '_aisb_phase1_enabled');
            delete_post_meta($row->post_id, '_aisb_phase1_sections');
            
            // Clear any cached data
            delete_transient('aisb_sections_' . $row->post_id);
            wp_cache_delete('aisb_enabled_' . $row->post_id, 'aisb');
        }
        
        // Clean up old database tables if they exist
        $table_name = $wpdb->prefix . 'aisb_sections';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        // Clean up old options
        delete_option('aisb_phase1a_version');
        delete_option('aisb_db_version');
        
        // Mark migration as done
        update_option('aisb_phase1b_migration', '1.0', false);
        
        // Log migration
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AISB: Phase 1B migration completed');
        }
    }
    
    /**
     * Get sections for a post with caching
     *
     * @param int $post_id Post ID
     * @param bool $use_cache Whether to use cache
     * @return array Sections array
     */
    public static function get_sections($post_id, $use_cache = true) {
        if ($use_cache) {
            $cached = get_transient('aisb_sections_' . $post_id);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $sections = get_post_meta($post_id, '_aisb_sections', true);
        
        if (!is_array($sections)) {
            $sections = [];
        }
        
        // Debug: Log sections loading for hero sections
        if (defined('WP_DEBUG') && WP_DEBUG) {
            foreach ($sections as $index => $section) {
                if (isset($section['type']) && ($section['type'] === 'hero' || $section['type'] === 'hero-form')) {
                    error_log('AISB Frontend Load - ' . $section['type'] . ' section ' . $index . ' heading: ' . 
                        (isset($section['content']['heading']) ? $section['content']['heading'] : 'NO HEADING'));
                }
            }
        }
        
        // Cache for 1 hour
        if ($use_cache) {
            set_transient('aisb_sections_' . $post_id, $sections, HOUR_IN_SECONDS);
        }
        
        return $sections;
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('aisb/v1', '/search-posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_posts'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => array(
                'search' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'post_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'any',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }
    
    /**
     * REST API callback for searching posts
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response
     */
    public function search_posts($request) {
        $search = $request->get_param('search');
        $post_type = $request->get_param('post_type');
        
        $args = array(
            'post_type' => $post_type === 'any' ? ['post', 'page'] : $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $posts = get_posts($args);
        
        $results = array();
        foreach ($posts as $post) {
            $results[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'url' => get_permalink($post->ID),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'aisb_enabled' => get_post_meta($post->ID, '_aisb_enabled', true) === '1'
            );
        }
        
        return new \WP_REST_Response($results, 200);
    }
    
    /**
     * Clear cache when post is saved
     *
     * @param int $post_id Post ID
     */
    public function clear_cache_on_save($post_id) {
        // Clear section cache
        delete_transient('aisb_sections_' . $post_id);
        
        // Clear enabled cache
        wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
        
        // Clear conflict notice if exists
        delete_transient('aisb_conflict_notice_' . $post_id);
    }
    
    /**
     * Clear cache when post is deleted
     *
     * @param int $post_id Post ID
     */
    public function clear_cache_on_delete($post_id) {
        // Clear all related transients
        delete_transient('aisb_sections_' . $post_id);
        delete_transient('aisb_conflict_notice_' . $post_id);
        
        // Clear cache
        wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
        
        // Clean up post meta
        delete_post_meta($post_id, '_aisb_enabled');
        delete_post_meta($post_id, '_aisb_sections');
    }
}
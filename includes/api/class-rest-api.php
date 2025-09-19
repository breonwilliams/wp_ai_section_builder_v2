<?php
/**
 * REST API Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\API;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles REST API endpoints for the editor
 */
class REST_API {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Register search endpoint for autocomplete
        register_rest_route('wp/v2', '/search-content', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'search_content'),
            'permission_callback' => array($this, 'search_permission_check'),
            'args'                => array(
                'search' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'per_page' => array(
                    'default'           => 10,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }
    
    /**
     * Permission check for search endpoint
     *
     * @return bool
     */
    public function search_permission_check() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Search content for autocomplete
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function search_content($request) {
        $search_term = $request->get_param('search');
        $per_page = $request->get_param('per_page');
        
        $results = array();
        
        // Search posts
        $posts_query = new \WP_Query(array(
            's'              => $search_term,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => ceil($per_page / 2),
            'no_found_rows'  => true,
        ));
        
        if ($posts_query->have_posts()) {
            foreach ($posts_query->posts as $post) {
                $results[] = array(
                    'type' => 'post',
                    'text' => $post->post_title,
                    'url'  => get_permalink($post->ID),
                );
            }
        }
        
        // Search pages
        $pages_query = new \WP_Query(array(
            's'              => $search_term,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => ceil($per_page / 2),
            'no_found_rows'  => true,
        ));
        
        if ($pages_query->have_posts()) {
            foreach ($pages_query->posts as $page) {
                $results[] = array(
                    'type' => 'page',
                    'text' => $page->post_title,
                    'url'  => get_permalink($page->ID),
                );
            }
        }
        
        // Add custom URL option if no results or always show it
        if (empty($results)) {
            $results[] = array(
                'type' => 'custom',
                'text' => __('Enter custom URL...', 'ai-section-builder'),
                'url'  => '',
            );
        }
        
        return new \WP_REST_Response(array(
            'results' => $results,
        ), 200);
    }
}
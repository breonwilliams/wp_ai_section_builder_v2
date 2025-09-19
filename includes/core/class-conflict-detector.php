<?php
/**
 * Conflict Detector Class
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
 * Detects and handles conflicts with other page builders
 */
class Conflict_Detector {
    
    /**
     * Known page builder meta keys
     *
     * @var array
     */
    private $builder_meta_keys = [
        'elementor' => '_elementor_data',
        'beaver-builder' => '_fl_builder_data',
        'divi' => '_et_pb_use_builder',
        'wpbakery' => '_wpb_vc_js_status',
        'breakdance' => '_breakdance_data',
        'bricks' => '_bricks_page_content_2'
    ];
    
    /**
     * Builder friendly names
     *
     * @var array
     */
    private $builder_names = [
        'elementor' => 'Elementor',
        'beaver-builder' => 'Beaver Builder',
        'divi' => 'Divi Builder',
        'wpbakery' => 'WPBakery Page Builder',
        'breakdance' => 'Breakdance',
        'bricks' => 'Bricks Builder',
        'aisb' => 'AI Section Builder'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Display admin notices for conflicts
        add_action('admin_notices', array($this, 'display_conflict_notices'));
    }
    
    /**
     * Detect active builders on a post
     *
     * @param int $post_id Post ID
     * @return array List of active builders
     */
    public function detect_active_builders($post_id) {
        $builders = [];
        
        // Check each known builder
        foreach ($this->builder_meta_keys as $builder => $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            
            // Special check for Divi
            if ($builder === 'divi' && $meta_value === 'on') {
                $builders[] = $builder;
            } elseif ($builder !== 'divi' && !empty($meta_value)) {
                $builders[] = $builder;
            }
        }
        
        // Check our plugin
        if ($this->is_aisb_enabled($post_id)) {
            $builders[] = 'aisb';
        }
        
        return $builders;
    }
    
    /**
     * Get friendly name for page builder
     *
     * @param string $builder Builder slug
     * @return string Friendly name
     */
    public function get_builder_name($builder) {
        return $this->builder_names[$builder] ?? ucfirst($builder);
    }
    
    /**
     * Check if AI Section Builder is enabled for a post
     *
     * @param int $post_id Post ID
     * @return bool
     */
    public function is_aisb_enabled($post_id) {
        $cache_key = 'aisb_enabled_' . $post_id;
        
        // Check cache first
        $cached = wp_cache_get($cache_key, 'aisb');
        if ($cached !== false) {
            return (bool) $cached;
        }
        
        // Check explicit enable flag
        $enabled = get_post_meta($post_id, '_aisb_enabled', true);
        
        // Must be explicitly enabled (1 or '1')
        $result = ($enabled == 1);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $result ? 1 : 0, 'aisb', HOUR_IN_SECONDS);
        
        return $result;
    }
    
    /**
     * Check if there are builder conflicts on a post
     *
     * @param int $post_id Post ID
     * @return array Array with conflict info
     */
    public function check_conflicts($post_id) {
        $active_builders = $this->detect_active_builders($post_id);
        $conflicts = array_diff($active_builders, ['aisb']);
        
        return [
            'has_conflicts' => !empty($conflicts),
            'conflicting_builders' => $conflicts,
            'all_builders' => $active_builders
        ];
    }
    
    /**
     * Display admin notices for conflicts
     */
    public function display_conflict_notices() {
        $screen = get_current_screen();
        
        // Only show on post edit screens
        if (!$screen || !in_array($screen->id, ['post', 'page'])) {
            return;
        }
        
        // Check for notices
        if (isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            
            // Check for conflict notices
            $conflicts = get_transient('aisb_conflict_notice_' . $post_id);
            if ($conflicts) {
                $builder_names = array_map([$this, 'get_builder_name'], $conflicts);
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                        <?php _e('Template override disabled due to conflicts with:', 'ai-section-builder'); ?>
                        <strong><?php echo esc_html(implode(', ', $builder_names)); ?></strong>
                    </p>
                    <p>
                        <a href="#aisb-meta-box" class="button button-secondary">
                            <?php _e('Resolve Conflict', 'ai-section-builder'); ?>
                        </a>
                    </p>
                </div>
                <?php
                delete_transient('aisb_conflict_notice_' . $post_id);
            }
        }
        
        // Check for success messages
        if (isset($_GET['aisb_activated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('AI Section Builder activated!', 'ai-section-builder'); ?></strong>
                    <?php _e('You can now start adding sections.', 'ai-section-builder'); ?>
                </p>
            </div>
            <?php
        }
    }
}
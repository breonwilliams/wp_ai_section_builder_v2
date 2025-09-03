<?php
/**
 * Page Builder Detection
 *
 * @package AISB\Builders
 * @since 2.0.0
 */

namespace AISB\Builders;

/**
 * Detects active page builders on posts/pages
 */
class BuilderDetector {
    /**
     * List of supported builders and their detection methods
     *
     * @var array
     */
    private $builders = [
        'elementor' => [
            'name' => 'Elementor',
            'meta_keys' => ['_elementor_data', '_elementor_edit_mode'],
            'active_check' => '_elementor_edit_mode'
        ],
        'beaver-builder' => [
            'name' => 'Beaver Builder',
            'meta_keys' => ['_fl_builder_data', '_fl_builder_enabled'],
            'active_check' => '_fl_builder_enabled'
        ],
        'divi' => [
            'name' => 'Divi Builder',
            'meta_keys' => ['_et_pb_use_builder', '_et_pb_page_layout'],
            'active_check' => '_et_pb_use_builder',
            'active_value' => 'on'
        ],
        'wpbakery' => [
            'name' => 'WPBakery Page Builder',
            'meta_keys' => ['_wpb_vc_js_status'],
            'active_check' => '_wpb_vc_js_status',
            'active_value' => 'true'
        ],
        'breakdance' => [
            'name' => 'Breakdance',
            'meta_keys' => ['_breakdance_data'],
            'active_check' => '_breakdance_data'
        ],
        'bricks' => [
            'name' => 'Bricks Builder',
            'meta_keys' => ['_bricks_page_content_2'],
            'active_check' => '_bricks_page_content_2'
        ]
    ];

    /**
     * Initialize the detector
     */
    public function init() {
        // Add any initialization if needed
    }

    /**
     * Detect active page builders on a post
     *
     * @param int $post_id Post ID
     * @return array Array of active builder slugs
     */
    public function detect_active_builders($post_id) {
        $active_builders = [];

        foreach ($this->builders as $slug => $config) {
            if ($this->is_builder_active($post_id, $config)) {
                $active_builders[] = $slug;
            }
        }

        // Check if AISB is active
        if ($this->is_aisb_active($post_id)) {
            $active_builders[] = 'aisb';
        }

        return $active_builders;
    }

    /**
     * Check if a specific builder is active
     *
     * @param int $post_id Post ID
     * @param array $config Builder configuration
     * @return bool
     */
    private function is_builder_active($post_id, $config) {
        $active_check = $config['active_check'];
        $meta_value = get_post_meta($post_id, $active_check, true);

        if (!$meta_value) {
            return false;
        }

        // Check for specific active value if defined
        if (isset($config['active_value'])) {
            return $meta_value === $config['active_value'];
        }

        // Otherwise, any non-empty value means active
        return !empty($meta_value);
    }

    /**
     * Check if AISB is active on a post
     *
     * @param int $post_id Post ID
     * @return bool
     */
    public function is_aisb_active($post_id) {
        $enabled = get_post_meta($post_id, '_aisb_enabled', true);
        return $enabled == 1;
    }

    /**
     * Get friendly name for a builder
     *
     * @param string $slug Builder slug
     * @return string
     */
    public function get_builder_name($slug) {
        if ($slug === 'aisb') {
            return 'AI Section Builder';
        }

        if (isset($this->builders[$slug])) {
            return $this->builders[$slug]['name'];
        }

        return ucfirst(str_replace('-', ' ', $slug));
    }

    /**
     * Check for builder conflicts
     *
     * @param int $post_id Post ID
     * @return array Conflict information
     */
    public function check_conflicts($post_id) {
        $active_builders = $this->detect_active_builders($post_id);
        $other_builders = array_diff($active_builders, ['aisb']);

        return [
            'has_conflicts' => count($active_builders) > 1 && in_array('aisb', $active_builders),
            'conflicting_builders' => $other_builders,
            'all_builders' => $active_builders
        ];
    }

    /**
     * Deactivate other builders on a post
     *
     * @param int $post_id Post ID
     * @return array Array of deactivated builders
     */
    public function deactivate_other_builders($post_id) {
        $active_builders = $this->detect_active_builders($post_id);
        $other_builders = array_diff($active_builders, ['aisb']);

        if (!empty($other_builders)) {
            // Store which builders we're switching from
            update_post_meta($post_id, '_aisb_switched_from', $other_builders);

            // Deactivate each builder
            foreach ($other_builders as $builder) {
                $this->deactivate_builder($post_id, $builder);
            }
        }

        return $other_builders;
    }

    /**
     * Deactivate a specific builder
     *
     * @param int $post_id Post ID
     * @param string $builder Builder slug
     */
    private function deactivate_builder($post_id, $builder) {
        switch ($builder) {
            case 'elementor':
                update_post_meta($post_id, '_elementor_edit_mode', '');
                break;
            
            case 'beaver-builder':
                update_post_meta($post_id, '_fl_builder_enabled', 0);
                break;
            
            case 'divi':
                update_post_meta($post_id, '_et_pb_use_builder', 'off');
                break;
            
            case 'wpbakery':
                update_post_meta($post_id, '_wpb_vc_js_status', 'false');
                break;
            
            // For Breakdance and Bricks, we don't deactivate data
            // as it might cause issues
        }
    }

    /**
     * Get all supported builders
     *
     * @return array
     */
    public function get_supported_builders() {
        $supported = [];
        foreach ($this->builders as $slug => $config) {
            $supported[$slug] = $config['name'];
        }
        $supported['aisb'] = 'AI Section Builder';
        return $supported;
    }
}
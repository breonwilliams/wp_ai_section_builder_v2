<?php
/**
 * Template Handler Class
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
 * Handles template overrides and canvas generation
 */
class Template_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Page builder functionality - override templates (late priority to avoid conflicts)
        add_filter('template_include', array($this, 'template_override'), 999);
        add_filter('body_class', array($this, 'add_body_class'));
    }
    
    /**
     * Override template for pages with AISB enabled
     *
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function template_override($template) {
        // Only on singular posts/pages
        if (!is_singular(['post', 'page'])) {
            return $template;
        }
        
        $post_id = get_the_ID();
        
        // Check if AISB is enabled for this post
        
        // Check for builder conflicts
        $conflict_check = aisb_check_conflicts($post_id);
        
        // If there are conflicts, don't override template
        if ($conflict_check['has_conflicts']) {
            // Set flag for admin notice
            set_transient('aisb_conflict_notice_' . $post_id, $conflict_check['conflicting_builders'], 300);
            return $template;
        }
        
        // Only override if AISB is the active builder
        if (!aisb_is_enabled($post_id)) {
            return $template;
        }
        // Load our custom template
        return $this->get_canvas_template();
    }
    
    /**
     * Add body class for styling
     *
     * @param array $classes Existing body classes
     * @return array Modified body classes
     */
    public function add_body_class($classes) {
        if (is_singular(['post', 'page']) && aisb_has_sections()) {
            $classes[] = 'aisb-canvas';
            $classes[] = 'aisb-template-override';
        }
        return $classes;
    }
    
    /**
     * Get canvas template - creates a full-width template
     *
     * @return string Path to canvas template
     */
    public function get_canvas_template() {
        // Use WordPress temp directory for better reliability
        $temp_template = get_temp_dir() . 'aisb-canvas-' . get_the_ID() . '-' . time() . '.php';
        
        // Create template content
        $template_content = $this->generate_canvas_template();
        
        // Write template file
        file_put_contents($temp_template, $template_content);
        
        // Clean up file after use
        add_action('wp_footer', function() use ($temp_template) {
            if (file_exists($temp_template)) {
                unlink($temp_template);
            }
        }, 999);
        
        return $temp_template;
    }
    
    /**
     * Generate canvas template content
     * This preserves theme header/footer but removes content area styling
     *
     * @return string Template content
     */
    private function generate_canvas_template() {
        ob_start();
        ?>
<?php
/**
 * AI Section Builder Canvas Template
 * This template is generated dynamically to preserve theme compatibility
 * while providing full-width section rendering
 */

// Get theme header (preserves navigation, styles, etc.)
get_header();

// Get our sections data (optimized with caching)
$post_id = get_the_ID();
$sections = aisb_get_sections($post_id);
?>

<div id="aisb-canvas" class="aisb-canvas">
    <style>
        /* Remove theme content constraints */
        .aisb-canvas .site-main,
        .aisb-canvas .entry-content,
        .aisb-canvas .page-content,
        .aisb-canvas .post-content,
        .aisb-canvas .content-area,
        .aisb-canvas main,
        .aisb-canvas article {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide ONLY theme page title - NOT our hero headings */
        .aisb-canvas > .entry-title,
        .aisb-canvas > .page-title,
        .aisb-canvas header.entry-header h1.entry-title,
        .aisb-canvas article > h1.entry-title {
            display: none !important;
        }
        
        /* Ensure our hero headings are always visible */
        .aisb-hero__heading,
        .aisb-hero-form__heading {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ensure proper width without overflow */
        .aisb-canvas {
            width: 100%;
            overflow-x: hidden;
        }
    </style>
    
    <?php
    // Phase 2A: Enable section rendering for Hero and Features sections
    if (!empty($sections) && is_array($sections)) {
        foreach ($sections as $section) {
            // Render section based on type
            if ($section['type'] === 'hero') {
                echo aisb_render_hero_section($section);
            } elseif ($section['type'] === 'hero-form') {
                echo aisb_render_hero_form_section($section);
            } elseif ($section['type'] === 'features') {
                echo aisb_render_features_section($section);
            } elseif ($section['type'] === 'checklist') {
                echo aisb_render_checklist_section($section);
            } elseif ($section['type'] === 'faq') {
                echo aisb_render_faq_section($section);
            } elseif ($section['type'] === 'stats') {
                echo aisb_render_stats_section($section);
            } elseif ($section['type'] === 'testimonials') {
                echo aisb_render_testimonials_section($section);
            }
        }
    } else {
        // Show placeholder if no sections
        ?>
        <div style="padding: 60px 20px; text-align: center; background: #f5f5f5;">
            <h2>AI Section Builder Active</h2>
            <p>Use the visual editor to add sections to this page.</p>
        </div>
        <?php
    }
    ?>
</div>

<?php
// Get theme footer (preserves footer widgets, scripts, etc.)
get_footer();
?>
        <?php
        return ob_get_clean();
    }
}
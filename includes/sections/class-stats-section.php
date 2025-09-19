<?php
/**
 * Stats Section Class
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Sections;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stats Section renderer
 * Phase 1: Core structure without repeatable stats items
 */
class Stats_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'stats';
    
    /**
     * Render the stats section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Debug: Log what we're getting (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AISB Stats Section Content: ' . print_r($content, true));
        }
        
        // Extract standardized fields (following Features pattern)
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? 'By the Numbers');
        $content_text = wp_kses_post($content['content'] ?? '<p>Our impact and achievements</p>');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        
        // Media fields - matching Features section
        $media_type = sanitize_text_field($content['media_type'] ?? 'none');
        $featured_image = esc_url($content['featured_image'] ?? '');
        $video_url = esc_url($content['video_url'] ?? '');
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'center');
        
        // Get global blocks (buttons for now)
        $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
        
        // Build section classes based on variants - matching Features structure
        $section_classes = array(
            'aisb-section',  // Base class required for theme inheritance
            'aisb-stats',    // Section type class - MUST be combined with aisb-section
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-stats__container">
                <!-- Top section with content (like Features) -->
                <div class="aisb-stats__top">
                    <div class="aisb-stats__content">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-stats__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-stats__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-stats__intro"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Render media based on type (matching Features structure exactly)
                    if ($media_type === 'image' && $featured_image): ?>
                        <div class="aisb-stats__media">
                            <img src="<?php echo $featured_image; ?>" 
                                 alt="<?php echo esc_attr($heading); ?>" 
                                 class="aisb-stats__image">
                        </div>
                    <?php elseif ($media_type === 'video' && $video_url): ?>
                        <div class="aisb-stats__media">
                            <?php 
                            // Check if it's a YouTube URL
                            if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                // Extract YouTube ID
                                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                                $youtube_id = isset($matches[1]) ? $matches[1] : '';
                                
                                if ($youtube_id): ?>
                                    <iframe class="aisb-stats__video" 
                                            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                            frameborder="0" 
                                            allowfullscreen></iframe>
                                <?php endif;
                            } else {
                                // Self-hosted video or other video source
                                ?>
                                <video controls class="aisb-stats__video">
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Stats grid -->
                <div class="aisb-stats__grid">
                    <?php 
                    // Get stats items array
                    $stats = isset($content['stats']) && is_array($content['stats']) ? $content['stats'] : array();
                    
                    if (!empty($stats)):
                        foreach ($stats as $stat):
                            $stat_number = esc_html($stat['number'] ?? '');
                            $stat_label = esc_html($stat['label'] ?? '');
                            $stat_description = esc_html($stat['description'] ?? '');
                            
                            if ($stat_number || $stat_label): // Only render if there's content
                    ?>
                        <div class="aisb-stats__item">
                            <?php if ($stat_number): ?>
                                <div class="aisb-stats__item-number"><?php echo $stat_number; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($stat_label): ?>
                                <div class="aisb-stats__item-label"><?php echo $stat_label; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($stat_description): ?>
                                <div class="aisb-stats__item-description"><?php echo $stat_description; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php 
                            endif;
                        endforeach;
                    else:
                        // Show placeholder stats if no actual stats
                    ?>
                        <div class="aisb-stats__item">
                            <div class="aisb-stats__item-number">99%</div>
                            <div class="aisb-stats__item-label">Customer Satisfaction</div>
                            <div class="aisb-stats__item-description">Based on 10,000+ reviews</div>
                        </div>
                        <div class="aisb-stats__item">
                            <div class="aisb-stats__item-number">50M+</div>
                            <div class="aisb-stats__item-label">Active Users</div>
                            <div class="aisb-stats__item-description">Across 120 countries</div>
                        </div>
                        <div class="aisb-stats__item">
                            <div class="aisb-stats__item-number">24/7</div>
                            <div class="aisb-stats__item-label">Support Available</div>
                            <div class="aisb-stats__item-description">Always here to help</div>
                        </div>
                        <div class="aisb-stats__item">
                            <div class="aisb-stats__item-number">4.9★</div>
                            <div class="aisb-stats__item-label">Average Rating</div>
                            <div class="aisb-stats__item-description">From industry experts</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Render global blocks (buttons for now)
                $buttons = array_filter($global_blocks, function($block) {
                    return isset($block['type']) && $block['type'] === 'button';
                });
                
                if (!empty($buttons)): ?>
                    <div class="aisb-stats__buttons">
                        <?php foreach ($buttons as $button): ?>
                            <?php if (!empty($button['text'])): ?>
                                <?php 
                                $btn_text = esc_html($button['text']);
                                $btn_url = esc_url($button['url'] ?? '#');
                                $btn_style = esc_attr($button['style'] ?? 'primary');
                                $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                                $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                                ?>
                                <a href="<?php echo $btn_url; ?>" 
                                   class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                                   target="<?php echo esc_attr($btn_target); ?>"
                                   <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                    <?php echo $btn_text; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($outro_content): ?>
                    <div class="aisb-stats__outro"><?php echo $outro_content; ?></div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
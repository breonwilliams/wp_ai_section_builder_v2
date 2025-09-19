<?php
/**
 * Testimonials Section Class
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
 * Testimonials Section renderer
 */
class Testimonials_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'testimonials';
    
    /**
     * Render the testimonials section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Debug: Log what we're getting (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AISB Testimonials Section Content: ' . print_r($content, true));
        }
        
        // Extract standardized fields (following Features pattern)
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? 'What Our Customers Say');
        $content_text = wp_kses_post($content['content'] ?? '<p>Hear from real people who have achieved amazing results with our solution.</p>');
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
            'aisb-testimonials',    // Section type class - MUST be combined with aisb-section
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-testimonials__container">
                <!-- Top section with content (like Features) -->
                <div class="aisb-testimonials__top">
                    <div class="aisb-testimonials__content">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-testimonials__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-testimonials__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-testimonials__intro"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Render media based on type (matching Features structure exactly)
                    if ($media_type === 'image' && $featured_image): ?>
                        <div class="aisb-testimonials__media">
                            <img src="<?php echo $featured_image; ?>" 
                                 alt="<?php echo esc_attr($heading); ?>" 
                                 class="aisb-testimonials__image">
                        </div>
                    <?php elseif ($media_type === 'video' && $video_url): ?>
                        <div class="aisb-testimonials__media">
                            <?php 
                            // Check if it's a YouTube URL
                            if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                // Extract YouTube ID
                                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                                $youtube_id = isset($matches[1]) ? $matches[1] : '';
                                
                                if ($youtube_id): ?>
                                    <iframe class="aisb-testimonials__video" 
                                            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                            frameborder="0" 
                                            allowfullscreen></iframe>
                                <?php endif;
                            } else {
                                // Self-hosted video or other video source
                                ?>
                                <video controls class="aisb-testimonials__video">
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Testimonials grid -->
                <?php 
                // Get testimonials array
                $testimonials = isset($content['testimonials']) && is_array($content['testimonials']) ? $content['testimonials'] : array();
                
                if (!empty($testimonials)): ?>
                    <div class="aisb-testimonials__grid">
                        <?php foreach ($testimonials as $testimonial): 
                            $rating = isset($testimonial['rating']) ? intval($testimonial['rating']) : 5;
                            $quote = isset($testimonial['content']) ? esc_html($testimonial['content']) : '';
                            $author_name = isset($testimonial['author_name']) ? esc_html($testimonial['author_name']) : 'Anonymous';
                            $author_title = isset($testimonial['author_title']) ? esc_html($testimonial['author_title']) : '';
                            $author_image = isset($testimonial['author_image']) ? esc_url($testimonial['author_image']) : '';
                        ?>
                            <div class="aisb-testimonials__item">
                                <div class="aisb-testimonials__rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $rating): ?>
                                            <span class="aisb-testimonials__star aisb-testimonials__star--filled">★</span>
                                        <?php else: ?>
                                            <span class="aisb-testimonials__star">☆</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($quote): ?>
                                    <div class="aisb-testimonials__quote">
                                        "<?php echo $quote; ?>"
                                    </div>
                                <?php endif; ?>
                                <div class="aisb-testimonials__author">
                                    <?php if ($author_image): ?>
                                        <img src="<?php echo $author_image; ?>" 
                                             alt="<?php echo $author_name; ?>" 
                                             class="aisb-testimonials__author-image">
                                    <?php endif; ?>
                                    <div class="aisb-testimonials__author-info">
                                        <div class="aisb-testimonials__author-name">
                                            <?php echo $author_name; ?>
                                        </div>
                                        <?php if ($author_title): ?>
                                            <div class="aisb-testimonials__author-title">
                                                <?php echo $author_title; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="aisb-testimonials__grid">
                        <div class="aisb-testimonials__placeholder">
                            <p>Testimonials coming soon! Add your first testimonial to get started.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Render global blocks (buttons for now)
                $buttons = array_filter($global_blocks, function($block) {
                    return isset($block['type']) && $block['type'] === 'button';
                });
                
                if (!empty($buttons)): ?>
                    <div class="aisb-testimonials__buttons">
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
                    <div class="aisb-testimonials__outro"><?php echo $outro_content; ?></div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
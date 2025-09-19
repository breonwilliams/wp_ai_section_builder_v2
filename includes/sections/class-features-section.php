<?php
/**
 * Features Section Class
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
 * Features Section renderer
 */
class Features_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'features';
    
    /**
     * Render the features section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Debug: Log what we're getting (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AISB Features Section Content: ' . print_r($content, true));
        }
        
        // Extract standardized fields
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? 'Our Features');
        $content_text = wp_kses_post($content['content'] ?? '<p>Discover what makes us different</p>');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
        $card_alignment = sanitize_text_field($content['card_alignment'] ?? 'left');
        
        // Get global blocks (buttons for now)
        $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
        
        // Build section classes based on variants - matching Hero structure
        $section_classes = array(
            'aisb-section',  // Base class required for theme inheritance
            'aisb-features', // Section type class - MUST be combined with aisb-section
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant,
            'aisb-features--cards-' . $card_alignment // Card alignment class
        );
        
        // Get media fields
        $featured_image = esc_url($content['featured_image'] ?? '');
        $media_type = sanitize_text_field($content['media_type'] ?? 'none');
        $video_url = esc_url($content['video_url'] ?? '');
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-features__container">
                <!-- Top section with content and optional media (like Hero) -->
                <div class="aisb-features__top">
                    <div class="aisb-features__content">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-features__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-features__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-features__intro"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Render media based on type (matching Hero structure)
                    if ($media_type === 'image' && $featured_image): ?>
                        <div class="aisb-features__media">
                            <img src="<?php echo $featured_image; ?>" 
                                 alt="<?php echo esc_attr($heading); ?>" 
                                 class="aisb-features__image">
                        </div>
                    <?php elseif ($media_type === 'video' && $video_url): ?>
                        <div class="aisb-features__media">
                            <?php 
                            // Check if it's a YouTube URL
                            if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                // Extract YouTube ID
                                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                                $youtube_id = isset($matches[1]) ? $matches[1] : '';
                                
                                if ($youtube_id): ?>
                                    <iframe class="aisb-features__video" 
                                            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                            frameborder="0" 
                                            allowfullscreen></iframe>
                                <?php endif;
                            } else {
                                // Direct video file
                                ?>
                                <video class="aisb-features__video" controls>
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Feature cards grid -->
                <?php 
                $cards = isset($content['cards']) ? $content['cards'] : array();
                if (!empty($cards)): 
                ?>
                    <div class="aisb-features__grid">
                        <?php foreach ($cards as $card): ?>
                            <div class="aisb-features__item">
                                <?php if (!empty($card['image'])): ?>
                                    <div class="aisb-features__item-image-wrapper">
                                        <img src="<?php echo esc_url($card['image']); ?>" 
                                             alt="<?php echo esc_attr($card['heading'] ?? ''); ?>" 
                                             class="aisb-features__item-image">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="aisb-features__item-content">
                                    <?php if (!empty($card['heading'])): ?>
                                        <h3 class="aisb-features__item-title"><?php echo esc_html($card['heading']); ?></h3>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($card['content'])): ?>
                                        <p class="aisb-features__item-description"><?php echo esc_html($card['content']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($card['link'])): ?>
                                        <?php
                                        $link_text = !empty($card['link_text']) ? esc_html($card['link_text']) : 'Learn More';
                                        $link_target = !empty($card['link_target']) && $card['link_target'] === '_blank' ? '_blank' : '_self';
                                        $link_rel = $link_target === '_blank' ? 'noopener noreferrer' : '';
                                        ?>
                                        <a href="<?php echo esc_url($card['link']); ?>" 
                                           class="aisb-features__item-link"
                                           target="<?php echo esc_attr($link_target); ?>"
                                           <?php if ($link_rel): ?>rel="<?php echo esc_attr($link_rel); ?>"<?php endif; ?>>
                                            <?php echo $link_text; ?> →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Render global blocks (buttons for now)
                $buttons = array_filter($global_blocks, function($block) {
                    return isset($block['type']) && $block['type'] === 'button';
                });
                
                if (!empty($buttons)): ?>
                    <div class="aisb-features__buttons">
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
                    <div class="aisb-features__outro"><?php echo $outro_content; ?></div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
<?php
/**
 * Hero Section Class
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
 * Hero Section renderer
 */
class Hero_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'hero';
    
    /**
     * Render the hero section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Migrate old field names to new structure (using global function for now)
        if (function_exists('aisb_migrate_field_names')) {
            $content = aisb_migrate_field_names($content);
        }
        
        // Debug: Log what we're getting (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AISB Hero Section Content: ' . print_r($content, true));
        }
        
        // Extract standardized fields
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? '');
        $content_text = wp_kses_post($content['content'] ?? '');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        $featured_image = esc_url($content['featured_image'] ?? '');
        $media_type = sanitize_text_field($content['media_type'] ?? 'none');
        $video_url = esc_url($content['video_url'] ?? '');
        
        // Debug: Log extracted media fields
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AISB Media - Type: $media_type, Image: $featured_image, Video: $video_url");
        }
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'dark');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
        
        // Get global blocks (buttons for now)
        $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
        
        // Build section classes based on variants
        $section_classes = array(
            'aisb-section',  // Base class required for theme inheritance
            'aisb-hero',
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-hero__container">
                <div class="aisb-hero__grid">
                    <div class="aisb-hero__content">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-hero__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h1 class="aisb-hero__heading"><?php echo $heading; ?></h1>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-hero__body"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <?php 
                        // Render global blocks (buttons for now)
                        $buttons = array_filter($global_blocks, function($block) {
                            return isset($block['type']) && $block['type'] === 'button';
                        });
                        
                        if (!empty($buttons)): ?>
                            <div class="aisb-hero__buttons">
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
                            <div class="aisb-hero__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php 
                    // Render media based on type
                    if ($media_type === 'image' && $featured_image): ?>
                        <div class="aisb-hero__media">
                            <img src="<?php echo $featured_image; ?>" 
                                 alt="<?php echo esc_attr($heading); ?>" 
                                 class="aisb-hero__image">
                        </div>
                    <?php elseif ($media_type === 'video' && $video_url): ?>
                        <div class="aisb-hero__media">
                            <?php 
                            // Check if it's a YouTube URL
                            $is_youtube = preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                            
                            if ($is_youtube && isset($matches[1])): 
                                $youtube_id = $matches[1];
                            ?>
                                <iframe class="aisb-hero__video" 
                                        src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            <?php else: ?>
                                <video class="aisb-hero__video" controls>
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
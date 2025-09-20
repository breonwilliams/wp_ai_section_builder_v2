<?php
/**
 * Checklist Section Class
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
 * Checklist Section renderer
 */
class Checklist_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'checklist';
    
    /**
     * Render the checklist section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Extract standardized fields (same as Features)
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? 'Everything You Need');
        $content_text = wp_kses_post($content['content'] ?? '<p>Our comprehensive solution includes:</p>');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
        
        // Get media fields
        $media_type = sanitize_text_field($content['media_type'] ?? 'none');
        $featured_image = esc_url($content['featured_image'] ?? '');
        $video_url = esc_url($content['video_url'] ?? '');
        
        // Get global blocks (buttons)
        $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
        if (is_string($global_blocks)) {
            $global_blocks = json_decode($global_blocks, true);
        }
        if (!is_array($global_blocks)) {
            $global_blocks = array();
        }
        
        // Get items (will be empty in Phase 1)
        $items = isset($content['items']) ? $content['items'] : array();
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items)) {
            $items = array();
        }
        
        // Build section classes
        $section_classes = array(
            'aisb-section',
            'aisb-checklist',
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-checklist__container">
                <?php if ($layout_variant !== 'center'): ?>
                    <!-- Two-column layout -->
                    <div class="aisb-checklist__columns">
                        <!-- Content Column -->
                        <div class="aisb-checklist__content-column">
                            <?php if ($eyebrow_heading): ?>
                                <div class="aisb-checklist__eyebrow"><?php echo $eyebrow_heading; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($heading): ?>
                                <h2 class="aisb-checklist__heading"><?php echo $heading; ?></h2>
                            <?php endif; ?>
                            
                            <?php if ($content_text): ?>
                                <div class="aisb-checklist__content"><?php echo $content_text; ?></div>
                            <?php endif; ?>
                            
                            <!-- Checklist Items -->
                            <?php if (!empty($items)): ?>
                                <?php echo $this->render_checklist_items($items); ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($global_blocks)): ?>
                                <div class="aisb-checklist__buttons">
                                    <?php foreach ($global_blocks as $block): ?>
                                        <?php if ($block['type'] === 'button'): ?>
                                            <?php
                                            $button_text = esc_html($block['text'] ?? 'Learn More');
                                            $button_url = esc_url($block['url'] ?? '#');
                                            $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                            $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                            $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                            ?>
                                            <a href="<?php echo $button_url; ?>" 
                                               class="<?php echo esc_attr($button_class); ?>"
                                               target="<?php echo esc_attr($button_target); ?>"
                                               <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                                <?php echo $button_text; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($outro_content): ?>
                                <div class="aisb-checklist__outro"><?php echo $outro_content; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Media Column (only if media exists and not center layout) -->
                        <?php if ($media_type !== 'none'): ?>
                            <div class="aisb-checklist__media-column">
                                <?php if ($media_type === 'image' && $featured_image): ?>
                                    <div class="aisb-checklist__media">
                                        <img src="<?php echo $featured_image; ?>" 
                                             alt="<?php echo esc_attr($heading); ?>" 
                                             class="aisb-checklist__image">
                                    </div>
                                <?php elseif ($media_type === 'video' && $video_url): ?>
                                    <div class="aisb-checklist__media">
                                        <?php
                                        // Check if YouTube
                                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                                            $video_id = $matches[1];
                                            ?>
                                            <iframe class="aisb-checklist__video" 
                                                    src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($video_id); ?>" 
                                                    frameborder="0" 
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                    allowfullscreen>
                                            </iframe>
                                        <?php } else { ?>
                                            <video class="aisb-checklist__video" controls>
                                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php } ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Center layout - single column, with media below content (matching hero/features) -->
                    <div class="aisb-checklist__center">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-checklist__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-checklist__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-checklist__content"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <!-- Checklist Items -->
                        <?php if (!empty($items)): ?>
                            <?php echo $this->render_checklist_items($items); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($global_blocks)): ?>
                            <div class="aisb-checklist__buttons">
                                <?php foreach ($global_blocks as $block): ?>
                                    <?php if ($block['type'] === 'button'): ?>
                                        <?php
                                        $button_text = esc_html($block['text'] ?? 'Learn More');
                                        $button_url = esc_url($block['url'] ?? '#');
                                        $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                        $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                        $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                        ?>
                                        <a href="<?php echo $button_url; ?>" 
                                           class="<?php echo esc_attr($button_class); ?>"
                                           target="<?php echo esc_attr($button_target); ?>"
                                           <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                            <?php echo $button_text; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($outro_content): ?>
                            <div class="aisb-checklist__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                        
                        <!-- Media below content for center layout (matching hero/features) -->
                        <?php if ($media_type !== 'none'): ?>
                            <?php if ($media_type === 'image' && $featured_image): ?>
                                <div class="aisb-checklist__media">
                                    <img src="<?php echo $featured_image; ?>" 
                                         alt="<?php echo esc_attr($heading); ?>" 
                                         class="aisb-checklist__image">
                                </div>
                            <?php elseif ($media_type === 'video' && $video_url): ?>
                                <div class="aisb-checklist__media">
                                    <?php
                                    // Check if YouTube
                                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)): 
                                        $youtube_id = $matches[1];
                                    ?>
                                        <iframe class="aisb-checklist__video" 
                                                src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    <?php else: ?>
                                        <video class="aisb-checklist__video" controls>
                                            <source src="<?php echo $video_url; ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render checklist items
     *
     * @param array $items Checklist items
     * @return string HTML output
     */
    private function render_checklist_items($items) {
        if (empty($items) || !is_array($items)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="aisb-checklist__items">
            <?php foreach ($items as $item): ?>
                <?php 
                $item_heading = esc_html($item['heading'] ?? 'Checklist Item');
                $item_content = esc_html($item['content'] ?? '');
                ?>
                <div class="aisb-checklist__item">
                    <div class="aisb-checklist__item-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M7 12L10 15L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="aisb-checklist__item-content">
                        <h4 class="aisb-checklist__item-heading"><?php echo $item_heading; ?></h4>
                        <?php if ($item_content): ?>
                            <p class="aisb-checklist__item-text"><?php echo $item_content; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
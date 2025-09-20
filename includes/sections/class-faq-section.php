<?php
/**
 * FAQ Section Class
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
 * FAQ Section renderer
 */
class FAQ_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'faq';
    
    /**
     * Render the FAQ section
     *
     * @return string HTML output
     */
    public function render() {
        // Handle both old and new section structure
        $content = isset($this->section['content']) ? $this->section['content'] : $this->section;
        
        // Extract standardized fields
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? 'Frequently Asked Questions');
        $content_text = wp_kses_post($content['content'] ?? '<p>Find answers to common questions about our services.</p>');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'center');
        
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
        
        // Get FAQ items (will be empty in Phase 1) 
        // Note: The field is actually 'questions' not 'faq_items' based on the document-upload.js
        $faq_items = isset($content['questions']) ? $content['questions'] : (isset($content['faq_items']) ? $content['faq_items'] : array());
        
        // Debug: Log FAQ data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AISB FAQ - Raw questions field: " . print_r(($content['questions'] ?? 'NOT SET'), true));
            error_log("AISB FAQ - Before decode: " . gettype($faq_items) . " - " . (is_string($faq_items) ? substr($faq_items, 0, 100) : 'not string'));
        }
        
        if (is_string($faq_items)) {
            $faq_items = json_decode($faq_items, true);
        }
        if (!is_array($faq_items)) {
            $faq_items = array();
        }
        
        // Debug: Log after decode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AISB FAQ - After decode, items count: " . count($faq_items));
            if (!empty($faq_items)) {
                error_log("AISB FAQ - First item: " . print_r($faq_items[0] ?? 'empty', true));
            }
        }
        
        // Build section classes
        $section_classes = array(
            'aisb-section',
            'aisb-faq',
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-faq__container">
                <?php if ($layout_variant !== 'center'): ?>
                    <!-- Two-column layout -->
                    <div class="aisb-faq__columns">
                        <!-- Content Column -->
                        <div class="aisb-faq__content-column">
                            <?php if ($eyebrow_heading): ?>
                                <div class="aisb-faq__eyebrow"><?php echo $eyebrow_heading; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($heading): ?>
                                <h2 class="aisb-faq__heading"><?php echo $heading; ?></h2>
                            <?php endif; ?>
                            
                            <?php if ($content_text): ?>
                                <div class="aisb-faq__content"><?php echo $content_text; ?></div>
                            <?php endif; ?>
                            
                            <!-- FAQ Items with Accordion -->
                            <?php if (!empty($faq_items)): ?>
                                <div class="aisb-faq__items">
                                    <?php foreach ($faq_items as $index => $item): ?>
                                        <div class="aisb-faq__item" data-faq-index="<?php echo $index; ?>">
                                            <?php if (!empty($item['question'])): ?>
                                                <h3 class="aisb-faq__item-question" data-faq-toggle="<?php echo $index; ?>"><?php echo esc_html($item['question']); ?></h3>
                                            <?php endif; ?>
                                            <?php if (!empty($item['answer'])): ?>
                                                <div class="aisb-faq__item-answer" data-faq-content="<?php echo $index; ?>">
                                                    <div class="aisb-faq__item-answer-inner">
                                                        <?php echo wp_kses_post($item['answer']); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($global_blocks)): ?>
                                <div class="aisb-faq__buttons">
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
                                <div class="aisb-faq__outro"><?php echo $outro_content; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Media Column (only if media exists and not center layout) -->
                        <?php if ($media_type !== 'none'): ?>
                            <div class="aisb-faq__media-column">
                                <?php if ($media_type === 'image' && $featured_image): ?>
                                    <div class="aisb-faq__media">
                                        <img src="<?php echo $featured_image; ?>" 
                                             alt="<?php echo esc_attr($heading); ?>" 
                                             class="aisb-faq__image">
                                    </div>
                                <?php elseif ($media_type === 'video' && $video_url): ?>
                                    <div class="aisb-faq__media">
                                        <?php
                                        // Check if YouTube
                                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                                            $video_id = $matches[1];
                                            ?>
                                            <iframe class="aisb-faq__video" 
                                                    src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($video_id); ?>" 
                                                    frameborder="0" 
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                    allowfullscreen>
                                            </iframe>
                                        <?php } else { ?>
                                            <video class="aisb-faq__video" controls>
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
                    <!-- Center layout - single column, with media below content -->
                    <div class="aisb-faq__center">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-faq__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-faq__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-faq__content"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <!-- FAQ Items with Accordion -->
                        <?php if (!empty($faq_items)): ?>
                            <div class="aisb-faq__items">
                                <?php foreach ($faq_items as $index => $item): ?>
                                    <div class="aisb-faq__item" data-faq-index="<?php echo $index; ?>">
                                        <?php if (!empty($item['question'])): ?>
                                            <h3 class="aisb-faq__item-question" data-faq-toggle="<?php echo $index; ?>"><?php echo esc_html($item['question']); ?></h3>
                                        <?php endif; ?>
                                        <?php if (!empty($item['answer'])): ?>
                                            <div class="aisb-faq__item-answer" data-faq-content="<?php echo $index; ?>">
                                                <div class="aisb-faq__item-answer-inner">
                                                    <?php echo wp_kses_post($item['answer']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($global_blocks)): ?>
                            <div class="aisb-faq__buttons">
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
                            <div class="aisb-faq__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                        
                        <!-- Media below content for center layout -->
                        <?php if ($media_type !== 'none'): ?>
                            <?php if ($media_type === 'image' && $featured_image): ?>
                                <div class="aisb-faq__media">
                                    <img src="<?php echo $featured_image; ?>" 
                                         alt="<?php echo esc_attr($heading); ?>" 
                                         class="aisb-faq__image">
                                </div>
                            <?php elseif ($media_type === 'video' && $video_url): ?>
                                <div class="aisb-faq__media">
                                    <?php
                                    // Check if YouTube
                                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)): 
                                        $youtube_id = $matches[1];
                                    ?>
                                        <iframe class="aisb-faq__video" 
                                                src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    <?php else: ?>
                                        <video class="aisb-faq__video" controls>
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
}
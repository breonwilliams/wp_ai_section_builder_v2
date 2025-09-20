<?php
/**
 * Hero Form Section Class
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
 * Hero Form Section renderer
 * Exactly like hero section but without media and with form
 */
class Hero_Form_Section extends Section_Base {
    
    /**
     * Section type
     *
     * @var string
     */
    protected $type = 'hero-form';
    
    /**
     * Render the hero form section
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
            error_log('AISB Hero Form Section Content: ' . print_r($content, true));
        }
        
        // Extract standardized fields
        $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
        $heading = esc_html($content['heading'] ?? '');
        $content_text = wp_kses_post($content['content'] ?? '');
        $outro_content = wp_kses_post($content['outro_content'] ?? '');
        
        // Extract form fields
        $form_type = sanitize_text_field($content['form_type'] ?? 'placeholder');
        $form_shortcode = $content['form_shortcode'] ?? '';
        
        // Get variants
        $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'dark');
        $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
        
        // Get global blocks (buttons for now)
        $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
        if (is_string($global_blocks)) {
            $global_blocks = json_decode($global_blocks, true);
        }
        if (!is_array($global_blocks)) {
            $global_blocks = array();
        }
        
        // Build section classes based on variants
        $section_classes = array(
            'aisb-section',  // Base class required for theme inheritance
            'aisb-hero-form',
            'aisb-section--' . $theme_variant,
            'aisb-section--' . $layout_variant
        );
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
            <div class="aisb-hero-form__container">
                <div class="aisb-hero-form__grid">
                    <div class="aisb-hero-form__content">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-hero-form__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h1 class="aisb-hero-form__heading"><?php echo $heading; ?></h1>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-hero-form__body"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <?php 
                        // Render global blocks (buttons for now)
                        $buttons = array_filter($global_blocks, function($block) {
                            return isset($block['type']) && $block['type'] === 'button';
                        });
                        
                        if (!empty($buttons)): ?>
                            <div class="aisb-hero-form__buttons">
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
                            <div class="aisb-hero-form__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="aisb-hero-form__form">
                        <?php 
                        // Render form based on type
                        if ($form_type === 'shortcode' && !empty($form_shortcode)) {
                            // Process shortcode
                            echo do_shortcode($form_shortcode);
                        } else {
                            // Show placeholder form
                            ?>
                            <div class="aisb-form-placeholder">
                                <form class="aisb-placeholder-form">
                                    <div class="aisb-form-field">
                                        <input type="text" placeholder="Name" disabled class="aisb-form-input">
                                    </div>
                                    <div class="aisb-form-field">
                                        <input type="email" placeholder="Email" disabled class="aisb-form-input">
                                    </div>
                                    <div class="aisb-form-field">
                                        <input type="tel" placeholder="Phone" disabled class="aisb-form-input">
                                    </div>
                                    <div class="aisb-form-field">
                                        <textarea placeholder="Message" disabled class="aisb-form-textarea" rows="4"></textarea>
                                    </div>
                                    <div class="aisb-form-field">
                                        <button type="button" class="aisb-btn aisb-btn-primary" disabled>Submit</button>
                                    </div>
                                </form>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
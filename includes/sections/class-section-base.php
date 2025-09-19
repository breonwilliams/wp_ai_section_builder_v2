<?php
/**
 * Base Section Class
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
 * Abstract base class for all section types
 */
abstract class Section_Base {
    
    /**
     * Section type identifier
     *
     * @var string
     */
    protected $type = '';
    
    /**
     * Section data
     *
     * @var array
     */
    protected $section = array();
    
    /**
     * Constructor
     *
     * @param array $section Section data
     */
    public function __construct($section = array()) {
        $this->section = $section;
    }
    
    /**
     * Render the section
     *
     * @return string HTML output
     */
    abstract public function render();
    
    /**
     * Get section classes
     *
     * @return string CSS classes
     */
    protected function get_section_classes() {
        $classes = array('aisb-section', 'aisb-' . $this->type);
        
        // Add theme class
        $theme = isset($this->section['theme']) ? $this->section['theme'] : 'light';
        $classes[] = 'aisb-section--' . $theme;
        
        // Add layout class if applicable
        if (isset($this->section['layout'])) {
            $classes[] = 'aisb-' . $this->type . '--' . $this->section['layout'];
        }
        
        // Add custom classes
        if (!empty($this->section['custom_class'])) {
            $classes[] = esc_attr($this->section['custom_class']);
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get section ID
     *
     * @return string Section ID attribute
     */
    protected function get_section_id() {
        if (!empty($this->section['section_id'])) {
            return 'id="' . esc_attr($this->section['section_id']) . '"';
        }
        return '';
    }
    
    /**
     * Render media (image or video)
     *
     * @param array $media Media data
     * @param string $class CSS class
     * @return string HTML output
     */
    protected function render_media($media, $class = '') {
        if (empty($media) || empty($media['type'])) {
            return '';
        }
        
        $html = '<div class="' . esc_attr($class) . '">';
        
        if ($media['type'] === 'image' && !empty($media['url'])) {
            $alt = !empty($media['alt']) ? $media['alt'] : '';
            $html .= '<img src="' . esc_url($media['url']) . '" alt="' . esc_attr($alt) . '">';
        } elseif ($media['type'] === 'video' && !empty($media['url'])) {
            $html .= '<video autoplay loop muted playsinline>';
            $html .= '<source src="' . esc_url($media['url']) . '" type="video/mp4">';
            $html .= '</video>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render buttons
     *
     * @param array $buttons Button data
     * @param string $wrapper_class Wrapper CSS class
     * @return string HTML output
     */
    protected function render_buttons($buttons, $wrapper_class = 'aisb-buttons') {
        if (empty($buttons) || !is_array($buttons)) {
            return '';
        }
        
        $html = '<div class="' . esc_attr($wrapper_class) . '">';
        
        foreach ($buttons as $button) {
            if (empty($button['text'])) {
                continue;
            }
            
            $style = isset($button['style']) ? $button['style'] : 'primary';
            $class = 'aisb-button aisb-button--' . $style;
            
            $html .= '<a href="' . esc_url($button['url'] ?? '#') . '" class="' . esc_attr($class) . '"';
            
            if (!empty($button['target'])) {
                $html .= ' target="' . esc_attr($button['target']) . '"';
                if ($button['target'] === '_blank') {
                    $html .= ' rel="noopener noreferrer"';
                }
            }
            
            $html .= '>' . esc_html($button['text']) . '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Sanitize and process content
     *
     * @param string $content Raw content
     * @return string Processed content
     */
    protected function process_content($content) {
        // Allow basic HTML tags
        $allowed_tags = array(
            'a' => array('href' => array(), 'target' => array(), 'rel' => array()),
            'strong' => array(),
            'em' => array(),
            'br' => array(),
            'span' => array('class' => array()),
        );
        
        $content = wp_kses($content, $allowed_tags);
        $content = wpautop($content);
        
        return $content;
    }
}
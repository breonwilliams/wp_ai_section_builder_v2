<?php
/**
 * Plugin Autoloader
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader class for AI Section Builder
 */
class AISB_Loader {
    
    /**
     * Plugin path
     *
     * @var string
     */
    private static $plugin_path = '';
    
    /**
     * Initialize the autoloader
     *
     * @param string $plugin_path Path to the plugin directory
     */
    public static function init($plugin_path) {
        self::$plugin_path = $plugin_path;
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
    
    /**
     * Autoload classes
     *
     * @param string $class Class name
     */
    public static function autoload($class) {
        // Only autoload our plugin's classes
        if (strpos($class, 'AISB\\') !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class = str_replace('AISB\\', '', $class);
        
        // Convert namespace separators to directory separators
        $class = str_replace('\\', '/', $class);
        
        // Convert class name to file name
        $class_parts = explode('/', $class);
        $class_name = array_pop($class_parts);
        $class_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        
        // Build the file path
        $subdirectory = !empty($class_parts) ? strtolower(implode('/', $class_parts)) . '/' : '';
        $file = self::$plugin_path . '/includes/' . $subdirectory . $class_name;
        
        // Load the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * Load section classes manually (for backward compatibility)
     */
    public static function load_section_classes() {
        $sections_path = self::$plugin_path . '/includes/sections/';
        
        // Load base class first
        if (file_exists($sections_path . 'class-section-base.php')) {
            require_once $sections_path . 'class-section-base.php';
        }
        
        // Load all section classes
        $section_files = array(
            'class-hero-section.php',
            'class-hero-form-section.php',
            'class-features-section.php',
            'class-checklist-section.php',
            'class-faq-section.php',
            'class-stats-section.php',
            'class-testimonials-section.php'
        );
        
        foreach ($section_files as $file) {
            $file_path = $sections_path . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
}
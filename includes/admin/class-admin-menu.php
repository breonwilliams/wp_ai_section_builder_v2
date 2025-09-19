<?php
/**
 * Admin Menu Management
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

namespace AISB\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu class
 */
class Admin_Menu {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
    }
    
    /**
     * Register admin menus
     */
    public function register_menus() {
        // Main menu
        add_menu_page(
            __('AI Section Builder', 'ai-section-builder'),
            __('AI Section Builder', 'ai-section-builder'),
            'manage_options',
            'ai-section-builder',
            array($this, 'render_dashboard_page'),
            'dashicons-layout',
            30
        );
        
        // Section Editor submenu
        add_submenu_page(
            'ai-section-builder',
            __('Section Editor', 'ai-section-builder'),
            __('Section Editor', 'ai-section-builder'),
            'edit_posts',
            'ai-section-builder-editor',
            array($this, 'render_editor_page')
        );
        
        // AI Settings submenu
        add_submenu_page(
            'ai-section-builder',
            __('AI Settings', 'ai-section-builder'),
            __('AI Settings', 'ai-section-builder'),
            'manage_options',
            'ai-section-builder-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $dashboard = new Admin_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Render editor page
     */
    public function render_editor_page() {
        // Call the global function for now - will be refactored later
        if (function_exists('aisb_render_editor_page')) {
            aisb_render_editor_page();
        }
    }
    
    /**
     * Render AI settings page
     */
    public function render_settings_page() {
        $settings = new AI_Settings();
        $settings->render();
    }
}
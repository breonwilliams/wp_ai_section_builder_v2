<?php
/**
 * Core Function Wrappers for Backward Compatibility
 *
 * These wrapper functions maintain backward compatibility for core functions
 * that have been moved to classes.
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

use AISB\Core\Template_Handler;
use AISB\Core\Conflict_Detector;
use AISB\Core\Utilities;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize global instances
global $aisb_conflict_detector, $aisb_template_handler;
$aisb_conflict_detector = null;
$aisb_template_handler = null;

/**
 * Get conflict detector instance
 */
function aisb_get_conflict_detector() {
    global $aisb_conflict_detector;
    if (!$aisb_conflict_detector) {
        $aisb_conflict_detector = new Conflict_Detector();
    }
    return $aisb_conflict_detector;
}

/**
 * Get template handler instance
 */
function aisb_get_template_handler() {
    global $aisb_template_handler;
    if (!$aisb_template_handler) {
        $aisb_template_handler = new Template_Handler();
    }
    return $aisb_template_handler;
}

// Template functions
function aisb_template_override($template) {
    $handler = aisb_get_template_handler();
    return $handler->template_override($template);
}

function aisb_body_class($classes) {
    $handler = aisb_get_template_handler();
    return $handler->add_body_class($classes);
}

function aisb_get_canvas_template() {
    $handler = aisb_get_template_handler();
    return $handler->get_canvas_template();
}

// Conflict detection functions
function aisb_detect_active_builders($post_id) {
    $detector = aisb_get_conflict_detector();
    return $detector->detect_active_builders($post_id);
}

function aisb_get_builder_name($builder) {
    $detector = aisb_get_conflict_detector();
    return $detector->get_builder_name($builder);
}

function aisb_is_enabled($post_id) {
    $detector = aisb_get_conflict_detector();
    return $detector->is_aisb_enabled($post_id);
}

function aisb_has_sections() {
    if (!is_singular(['post', 'page'])) {
        return false;
    }
    return aisb_is_enabled(get_the_ID());
}

function aisb_check_conflicts($post_id) {
    $detector = aisb_get_conflict_detector();
    return $detector->check_conflicts($post_id);
}

// Utility functions
function aisb_migrate_field_names($content) {
    return Utilities::migrate_field_names($content);
}

function aisb_migrate_cleanup_old_data() {
    Utilities::migrate_cleanup_old_data();
}

function aisb_get_sections($post_id, $use_cache = true) {
    return Utilities::get_sections($post_id, $use_cache);
}
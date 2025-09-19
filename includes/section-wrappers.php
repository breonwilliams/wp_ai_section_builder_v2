<?php
/**
 * Section Render Wrappers for Backward Compatibility
 *
 * These functions maintain backward compatibility by wrapping the new class-based
 * section renderers. They can be called from anywhere in the plugin that expects
 * the old function-based approach.
 *
 * @package AI_Section_Builder
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Hero Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_hero_section($section) {
    $hero = new \AISB\Sections\Hero_Section($section);
    return $hero->render();
}

/**
 * Render Hero Form Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_hero_form_section($section) {
    $hero_form = new \AISB\Sections\Hero_Form_Section($section);
    return $hero_form->render();
}

/**
 * Render Features Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_features_section($section) {
    $features = new \AISB\Sections\Features_Section($section);
    return $features->render();
}

/**
 * Render Checklist Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_checklist_section($section) {
    $checklist = new \AISB\Sections\Checklist_Section($section);
    return $checklist->render();
}

/**
 * Render FAQ Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_faq_section($section) {
    $faq = new \AISB\Sections\Faq_Section($section);
    return $faq->render();
}

/**
 * Render Stats Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_stats_section($section) {
    $stats = new \AISB\Sections\Stats_Section($section);
    return $stats->render();
}

/**
 * Render Testimonials Section
 *
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_testimonials_section($section) {
    $testimonials = new \AISB\Sections\Testimonials_Section($section);
    return $testimonials->render();
}
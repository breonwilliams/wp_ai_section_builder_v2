/**
 * AI Section Builder - FAQ Accordion
 * Handles expand/collapse functionality for FAQ items
 * 
 * @package AISB
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize FAQ accordions
     */
    function initFAQAccordions() {
        console.log('AISB FAQ: Initializing accordions');
        
        // Check if jQuery is available
        if (typeof jQuery === 'undefined') {
            console.error('AISB FAQ: jQuery is not loaded');
            return;
        }
        
        var $ = jQuery;
        
        // Remove any existing event handlers to prevent duplicates
        $(document).off('click', '.aisb-faq__item-question');
        
        // Handle click on FAQ questions
        $(document).on('click', '.aisb-faq__item-question', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('AISB FAQ: Question clicked');
            
            var $question = $(this);
            var $item = $question.closest('.aisb-faq__item');
            var $answer = $item.find('.aisb-faq__item-answer');
            var isExpanded = $item.hasClass('aisb-faq__item--expanded');
            
            console.log('AISB FAQ: Toggle state', { isExpanded: isExpanded });
            
            // Toggle expanded state
            if (isExpanded) {
                // Collapse
                $item.removeClass('aisb-faq__item--expanded');
                $answer.css('max-height', '0');
            } else {
                // Get actual height for smooth animation
                var actualHeight = $answer.prop('scrollHeight');
                console.log('AISB FAQ: Actual height', actualHeight);
                
                // Expand
                $item.addClass('aisb-faq__item--expanded');
                $answer.css('max-height', actualHeight + 'px');
            }
        });
        
        // Optional: Close other items when opening one (accordion behavior)
        // Uncomment if you want only one item open at a time
        /*
        $(document).on('click', '.aisb-faq__item-question', function(e) {
            e.preventDefault();
            
            var $question = $(this);
            var $item = $question.closest('.aisb-faq__item');
            var $container = $item.closest('.aisb-faq__items');
            var isExpanded = $item.hasClass('aisb-faq__item--expanded');
            
            // Close all other items
            $container.find('.aisb-faq__item').not($item).each(function() {
                $(this).removeClass('aisb-faq__item--expanded');
                $(this).find('.aisb-faq__item-answer').css('max-height', '0');
            });
            
            // Toggle current item
            if (!isExpanded) {
                var $answer = $item.find('.aisb-faq__item-answer');
                var actualHeight = $answer.prop('scrollHeight');
                
                $item.addClass('aisb-faq__item--expanded');
                $answer.css('max-height', actualHeight + 'px');
            }
        });
        */
    }
    
    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFAQAccordions);
    } else {
        // DOM is already loaded
        initFAQAccordions();
    }
    
    /**
     * Also initialize with jQuery if available
     */
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            initFAQAccordions();
        });
        
        // Re-initialize after full page load
        jQuery(window).on('load', function() {
            initFAQAccordions();
        });
    }

})();
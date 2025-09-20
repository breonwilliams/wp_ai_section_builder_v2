/**
 * AI Section Builder - FAQ Accordion
 * Handles accordion functionality for FAQ sections
 * 
 * @package AISB
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize FAQ accordion functionality
     */
    function initFaqAccordion() {
        console.log('FAQ Accordion: Initializing...');
        
        // Check if FAQ items exist
        var $faqItems = $('.aisb-faq__item-question');
        console.log('FAQ Accordion: Found', $faqItems.length, 'FAQ items');
        
        // Handle FAQ item click events (using event delegation)
        $(document).off('click.faq-accordion').on('click.faq-accordion', '.aisb-faq__item-question', function(e) {
            e.preventDefault();
            console.log('FAQ Accordion: Question clicked');
            
            var $question = $(this);
            var $item = $question.closest('.aisb-faq__item');
            var $allItems = $item.closest('.aisb-faq__items').find('.aisb-faq__item');
            
            console.log('FAQ Accordion: Found', $allItems.length, 'items in group');
            
            // Close other items (optional - remove this block for multi-open)
            $allItems.not($item).removeClass('aisb-faq__item--expanded');
            
            // Toggle current item
            var wasExpanded = $item.hasClass('aisb-faq__item--expanded');
            $item.toggleClass('aisb-faq__item--expanded');
            
            console.log('FAQ Accordion: Item', wasExpanded ? 'collapsed' : 'expanded');
        });
        
        // Debug: Log existing expanded items
        var $expanded = $('.aisb-faq__item--expanded');
        console.log('FAQ Accordion: Found', $expanded.length, 'pre-expanded items');
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initFaqAccordion();
    });
    
    // Re-initialize on AJAX updates (for editor preview)
    $(document).on('aisb:preview:updated', function() {
        console.log('FAQ Accordion: Preview updated, reinitializing...');
        setTimeout(function() {
            initFaqAccordion();
        }, 100); // Small delay to ensure DOM is updated
    });
    
})(jQuery);
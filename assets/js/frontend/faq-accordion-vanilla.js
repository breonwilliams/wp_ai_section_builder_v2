/**
 * AI Section Builder - FAQ Accordion
 * Production-ready accordion functionality with smooth CSS animations
 * 
 * @package AISB
 * @version 2.0.0
 */

(function() {
    'use strict';

    let initialized = false;

    /**
     * Initialize FAQ accordions
     */
    function initFAQAccordions() {
        // Prevent multiple initializations
        if (initialized) return;
        
        // Get all FAQ items
        const items = document.querySelectorAll('.aisb-faq__item');
        
        if (items.length === 0) {
            return;
        }
        
        // Initialize each FAQ item
        items.forEach(function(item) {
            const question = item.querySelector('.aisb-faq__item-question');
            
            if (!question) return;
            
            // Ensure initial state is collapsed
            item.classList.remove('aisb-faq__item--expanded');
            
            // Add click handler
            question.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                toggleFAQItem(item);
            });
        });
        
        initialized = true;
    }
    
    /**
     * Toggle FAQ item open/closed
     * Uses pure CSS classes for smooth animation
     */
    function toggleFAQItem(item) {
        const isExpanded = item.classList.contains('aisb-faq__item--expanded');
        
        if (isExpanded) {
            // Collapse
            item.classList.remove('aisb-faq__item--expanded');
        } else {
            // Expand
            item.classList.add('aisb-faq__item--expanded');
        }
    }
    
    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFAQAccordions);
    } else {
        // DOM is already loaded - initialize with small delay
        setTimeout(initFAQAccordions, 100);
    }
    
    // Also initialize on window load as backup
    window.addEventListener('load', initFAQAccordions);

})();
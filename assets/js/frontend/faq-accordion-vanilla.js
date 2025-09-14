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
        items.forEach(function(item, index) {
            const question = item.querySelector('.aisb-faq__item-question');
            const answer = item.querySelector('.aisb-faq__item-answer');
            
            if (!question || !answer) return;
            
            // Generate unique IDs for ARIA
            const questionId = 'aisb-faq-question-' + index;
            const answerId = 'aisb-faq-answer-' + index;
            
            // Add ARIA attributes
            question.setAttribute('id', questionId);
            question.setAttribute('aria-controls', answerId);
            question.setAttribute('aria-expanded', 'false');
            question.setAttribute('role', 'button');
            question.setAttribute('tabindex', '0');
            
            answer.setAttribute('id', answerId);
            answer.setAttribute('aria-labelledby', questionId);
            answer.setAttribute('role', 'region');
            
            // Ensure initial state is collapsed
            item.classList.remove('aisb-faq__item--expanded');
            
            // Add click handler
            question.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                toggleFAQItem(item);
            });
            
            // Add keyboard support (Enter and Space keys)
            question.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    toggleFAQItem(item);
                }
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
        const question = item.querySelector('.aisb-faq__item-question');
        
        if (isExpanded) {
            // Collapse
            item.classList.remove('aisb-faq__item--expanded');
            if (question) {
                question.setAttribute('aria-expanded', 'false');
            }
        } else {
            // Expand
            item.classList.add('aisb-faq__item--expanded');
            if (question) {
                question.setAttribute('aria-expanded', 'true');
            }
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
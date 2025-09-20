/**
 * AI Section Builder - Data Normalizer
 * Standardizes data format between AI, Editor, Database, and Frontend
 * 
 * @package AISB
 * @since 2.0.0
 */

(function(window) {
    'use strict';
    
    /**
     * Data Normalizer - Handles consistent data transformation
     */
    window.AISBDataNormalizer = {
        
        /**
         * List of fields that should be JSON arrays
         */
        ARRAY_FIELDS: [
            'cards',        // Features
            'items',        // Checklist
            'questions',    // FAQ
            'faq_items',    // FAQ (legacy)
            'stats',        // Stats
            'testimonials', // Testimonials
            'global_blocks' // Buttons
        ],
        
        /**
         * Normalize section data to ensure consistent types
         * @param {Object} section - Section object to normalize
         * @returns {Object} Normalized section
         */
        normalizeSection: function(section) {
            if (!section || !section.content) {
                return section;
            }
            
            var normalized = JSON.parse(JSON.stringify(section)); // Deep clone
            
            // Normalize all array fields
            this.ARRAY_FIELDS.forEach(function(fieldName) {
                if (normalized.content[fieldName]) {
                    normalized.content[fieldName] = this.normalizeArrayField(
                        normalized.content[fieldName], 
                        fieldName
                    );
                }
            }.bind(this));
            
            // Special handling for FAQ field mapping
            if (normalized.content.questions && !normalized.content.faq_items) {
                normalized.content.faq_items = normalized.content.questions;
            } else if (normalized.content.faq_items && !normalized.content.questions) {
                normalized.content.questions = normalized.content.faq_items;
            }
            
            return normalized;
        },
        
        /**
         * Normalize array field to ensure it's always an array
         * @param {any} value - Value to normalize
         * @param {string} fieldName - Field name for debugging
         * @returns {Array} Normalized array
         */
        normalizeArrayField: function(value, fieldName) {
            if (Array.isArray(value)) {
                return value;
            }
            
            if (typeof value === 'string') {
                try {
                    var parsed = JSON.parse(value);
                    if (Array.isArray(parsed)) {
                        console.log('DataNormalizer: Converted JSON string to array for field:', fieldName);
                        return parsed;
                    }
                } catch (e) {
                    console.warn('DataNormalizer: Invalid JSON string for field ' + fieldName + ':', e);
                }
            }
            
            if (value === null || value === undefined) {
                return [];
            }
            
            // If it's an object but not an array, wrap it in an array
            if (typeof value === 'object') {
                console.warn('DataNormalizer: Converting object to array for field ' + fieldName + ':', value);
                return [value];
            }
            
            console.warn('DataNormalizer: Unknown type for field ' + fieldName + ', using empty array:', typeof value, value);
            return [];
        },
        
        /**
         * Normalize multiple sections
         * @param {Array} sections - Array of sections to normalize
         * @returns {Array} Normalized sections
         */
        normalizeSections: function(sections) {
            if (!Array.isArray(sections)) {
                console.warn('DataNormalizer: sections is not an array:', sections);
                return [];
            }
            
            return sections.map(function(section) {
                return this.normalizeSection(section);
            }.bind(this));
        },
        
        /**
         * Prepare section for saving (convert arrays back to JSON strings if needed)
         * @param {Object} section - Section to prepare for saving
         * @returns {Object} Section prepared for database
         */
        prepareForSave: function(section) {
            // For now, we'll keep everything as arrays/objects
            // The backend can handle the serialization
            return this.normalizeSection(section);
        },
        
        /**
         * Debug: Log data type information
         * @param {Object} section - Section to debug
         * @param {string} context - Context for debugging
         */
        debugDataTypes: function(section, context) {
            if (!window.console || !console.log) return;
            
            console.group('DataNormalizer Debug: ' + context);
            console.log('Section type:', section.type);
            
            this.ARRAY_FIELDS.forEach(function(fieldName) {
                if (section.content && section.content[fieldName] !== undefined) {
                    var value = section.content[fieldName];
                    console.log(fieldName + ':', {
                        type: typeof value,
                        isArray: Array.isArray(value),
                        length: Array.isArray(value) ? value.length : 'N/A',
                        value: value
                    });
                }
            });
            
            console.groupEnd();
        }
    };
    
    // Expose for use in other files
    if (typeof jQuery !== 'undefined') {
        jQuery.extend(window, {
            normalizeSection: window.AISBDataNormalizer.normalizeSection.bind(window.AISBDataNormalizer),
            normalizeSections: window.AISBDataNormalizer.normalizeSections.bind(window.AISBDataNormalizer)
        });
    }
    
})(window);
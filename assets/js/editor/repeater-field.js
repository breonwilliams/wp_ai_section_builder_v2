/**
 * AI Section Builder - Repeater Field Module
 * Reusable infrastructure for dynamic repeatable fields
 * 
 * @package AISB
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Repeater Field Constructor
     */
    window.AISBRepeaterField = function(options) {
        this.fieldName = options.fieldName;
        this.container = options.container;
        this.template = options.template;
        this.defaultItem = options.defaultItem || {};
        this.maxItems = options.maxItems || 10;
        this.minItems = options.minItems || 0;
        this.items = options.items || [];
        this.onUpdate = options.onUpdate || function() {};
        this.itemLabel = options.itemLabel || 'Item';
        this.addButtonText = options.addButtonText || 'Add Item';
        this.idCounter = 0;
        
        this.init();
    };
    
    /**
     * Repeater Field Prototype Methods
     */
    AISBRepeaterField.prototype = {
        /**
         * Initialize repeater field
         */
        init: function() {
            this.render();
            this.bindEvents();
            this.initializeSortable();
        },
        
        /**
         * Generate unique ID for items
         */
        generateId: function() {
            return this.fieldName + '_' + Date.now() + '_' + (++this.idCounter);
        },
        
        /**
         * Add new item
         */
        addItem: function(itemData) {
            if (this.items.length >= this.maxItems) {
                this.showNotification('Maximum items reached (' + this.maxItems + ')', 'warning');
                return false;
            }
            
            var newItem = $.extend({}, this.defaultItem, itemData || {});
            newItem.id = newItem.id || this.generateId();
            
            this.items.push(newItem);
            this.render();
            this.onUpdate(this.items);
            
            // Focus on first input of new item
            var $newItem = this.container.find('[data-item-id="' + newItem.id + '"]');
            $newItem.find('input, select, textarea').first().focus();
            
            return newItem.id;
        },
        
        /**
         * Remove item
         */
        removeItem: function(itemId) {
            if (this.items.length <= this.minItems) {
                this.showNotification('Minimum items reached (' + this.minItems + ')', 'warning');
                return false;
            }
            
            var itemIndex = this.items.findIndex(function(item) {
                return item.id === itemId;
            });
            
            if (itemIndex > -1) {
                this.items.splice(itemIndex, 1);
                this.render();
                this.onUpdate(this.items);
                
                // Announce to screen readers
                this.announce('Item removed');
                return true;
            }
            
            return false;
        },
        
        /**
         * Update item data
         */
        updateItem: function(itemId, field, value) {
            var item = this.items.find(function(item) {
                return item.id === itemId;
            });
            
            if (item) {
                item[field] = value;
                this.onUpdate(this.items);
                return true;
            }
            
            return false;
        },
        
        /**
         * Render repeater UI
         */
        render: function() {
            var self = this;
            var html = '';
            
            // Render items
            this.items.forEach(function(item, index) {
                html += self.renderItem(item, index);
            });
            
            // Add button
            if (this.items.length < this.maxItems) {
                html += this.renderAddButton();
            }
            
            // Update container
            this.container.html(html);
            
            // Reinitialize sortable if it exists
            if (this.sortable) {
                this.initializeSortable();
            }
        },
        
        /**
         * Render individual item
         */
        renderItem: function(item, index) {
            var itemHtml = this.template(item, index);
            
            // Wrap in container with controls
            return '<div class="aisb-repeater-item" data-item-id="' + item.id + '">' +
                   '<div class="aisb-repeater-item__header">' +
                   '<span class="aisb-repeater-item__handle" aria-label="Drag to reorder">' +
                   '<span class="dashicons dashicons-move"></span>' +
                   '</span>' +
                   '<span class="aisb-repeater-item__title">' + this.itemLabel + ' ' + (index + 1) + '</span>' +
                   '<button type="button" class="aisb-repeater-item__remove" ' +
                   'aria-label="Remove ' + this.itemLabel.toLowerCase() + '" data-item-id="' + item.id + '">' +
                   '<span class="dashicons dashicons-no-alt"></span>' +
                   '</button>' +
                   '</div>' +
                   '<div class="aisb-repeater-item__content">' +
                   itemHtml +
                   '</div>' +
                   '</div>';
        },
        
        /**
         * Render add button
         */
        renderAddButton: function() {
            return '<button type="button" class="aisb-repeater-add">' +
                   '<span class="dashicons dashicons-plus-alt2"></span> ' +
                   this.addButtonText +
                   '</button>';
        },
        
        /**
         * Bind events using delegation
         */
        bindEvents: function() {
            var self = this;
            
            // Remove item
            this.container.on('click', '.aisb-repeater-item__remove', function(e) {
                e.preventDefault();
                var itemId = $(this).data('item-id');
                self.removeItem(itemId);
            });
            
            // Add item
            this.container.on('click', '.aisb-repeater-add', function(e) {
                e.preventDefault();
                self.addItem();
            });
            
            // Update item fields
            this.container.on('input change', '.aisb-repeater-field', function() {
                var $item = $(this).closest('.aisb-repeater-item');
                var itemId = $item.data('item-id');
                var field = $(this).data('field');
                var value = $(this).val();
                
                self.updateItem(itemId, field, value);
            });
        },
        
        /**
         * Initialize sortable functionality
         */
        initializeSortable: function() {
            var self = this;
            
            // Check if Sortable.js is available
            if (typeof Sortable === 'undefined') {
                return;
            }
            
            // Destroy existing instance
            if (this.sortable) {
                this.sortable.destroy();
            }
            
            // Create new sortable instance
            var container = this.container.get(0);
            if (!container) return;
            
            this.sortable = Sortable.create(container, {
                handle: '.aisb-repeater-item__handle',
                animation: 150,
                ghostClass: 'aisb-repeater-item--ghost',
                dragClass: 'aisb-repeater-item--drag',
                onEnd: function(evt) {
                    // Update item order in array
                    var item = self.items.splice(evt.oldIndex, 1)[0];
                    self.items.splice(evt.newIndex, 0, item);
                    self.onUpdate(self.items);
                }
            });
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Use existing notification system if available
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                console.warn('AISB Repeater: ' + message);
            }
        },
        
        /**
         * Announce to screen readers
         */
        announce: function(message) {
            var $announcement = $('#aisb-screen-reader-announcement');
            if (!$announcement.length) {
                $announcement = $('<div id="aisb-screen-reader-announcement" ' +
                                'class="screen-reader-text" aria-live="polite"></div>');
                $('body').append($announcement);
            }
            $announcement.text(message);
        },
        
        /**
         * Get items data
         */
        getData: function() {
            return this.items;
        },
        
        /**
         * Set items data
         */
        setData: function(items) {
            this.items = items || [];
            this.render();
        },
        
        /**
         * Destroy repeater instance
         */
        destroy: function() {
            this.container.off();
            if (this.sortable) {
                this.sortable.destroy();
            }
        }
    };
    
    /**
     * jQuery Plugin
     */
    $.fn.aisbRepeaterField = function(options) {
        return this.each(function() {
            var $this = $(this);
            var instance = $this.data('aisbRepeater');
            
            if (!instance) {
                instance = new AISBRepeaterField($.extend({}, options, {
                    container: $this
                }));
                $this.data('aisbRepeater', instance);
            }
            
            return instance;
        });
    };
    
})(jQuery);
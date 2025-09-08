/**
 * AI Section Builder - Global Settings JavaScript
 * Handles global settings panel interactions
 * 
 * @package AISB
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    // Global Settings Manager
    var GlobalSettings = {
        // Current settings state
        currentSettings: {},
        originalSettings: {},
        isDirty: false,
        
        /**
         * Initialize global settings
         */
        init: function() {
            this.bindEvents();
            this.loadCurrentSettings();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Open panel
            $('#aisb-global-settings-btn').on('click', function(e) {
                e.preventDefault();
                self.openPanel();
            });
            
            // Close panel
            $('#aisb-close-global-settings, #aisb-cancel-global-settings').on('click', function(e) {
                e.preventDefault();
                self.closePanel();
            });
            
            // Close on overlay click
            $('.aisb-global-settings-panel__overlay').on('click', function() {
                self.closePanel();
            });
            
            // Tab switching
            $('.aisb-global-settings-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Color input sync
            $('.aisb-color-input-wrapper input[type="color"]').on('input', function() {
                var $textInput = $(this).siblings('.aisb-color-text');
                $textInput.val($(this).val());
                self.updatePreview();
                self.markDirty();
            });
            
            $('.aisb-color-input-wrapper .aisb-color-text').on('input', function() {
                var $colorInput = $(this).siblings('input[type="color"]');
                var value = $(this).val();
                
                // Validate hex color
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    $colorInput.val(value);
                    self.updatePreview();
                    self.markDirty();
                }
            });
            
            // Number input changes
            $('.aisb-global-settings-panel input[type="number"]').on('input', function() {
                self.validateNumberInput($(this));
                self.updatePreview();
                self.markDirty();
            });
            
            // Text input changes
            $('.aisb-global-settings-panel input[type="text"]:not(.aisb-color-text)').on('input', function() {
                self.updatePreview();
                self.markDirty();
            });
            
            // Save settings
            $('#aisb-save-global-settings').on('click', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
            
            // Reset settings
            $('#aisb-reset-global-settings').on('click', function(e) {
                e.preventDefault();
                self.resetSettings();
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Cmd/Ctrl + , to open settings
                if ((e.metaKey || e.ctrlKey) && e.key === ',') {
                    e.preventDefault();
                    self.openPanel();
                }
                
                // Escape to close
                if (e.key === 'Escape' && $('#aisb-global-settings-panel').hasClass('active')) {
                    self.closePanel();
                }
            });
        },
        
        /**
         * Open settings panel
         */
        openPanel: function() {
            $('#aisb-global-settings-panel').show().addClass('active');
            $('body').css('overflow', 'hidden');
            this.loadCurrentSettings();
        },
        
        /**
         * Close settings panel
         */
        closePanel: function() {
            var self = this;
            
            if (this.isDirty) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }
            
            $('#aisb-global-settings-panel').removeClass('active');
            setTimeout(function() {
                $('#aisb-global-settings-panel').hide();
            }, 300);
            $('body').css('overflow', '');
            
            // Reset to original if not saved
            if (this.isDirty) {
                this.restoreOriginalSettings();
            }
        },
        
        /**
         * Switch tabs
         */
        switchTab: function(tab) {
            // Update tab buttons
            $('.aisb-global-settings-tab').removeClass('active');
            $('.aisb-global-settings-tab[data-tab="' + tab + '"]').addClass('active');
            
            // Update tab content
            $('.aisb-global-settings-tab-content').removeClass('active');
            $('.aisb-global-settings-tab-content[data-tab-content="' + tab + '"]').addClass('active');
        },
        
        /**
         * Load current settings from server
         */
        loadCurrentSettings: function() {
            var self = this;
            
            $.ajax({
                url: aisbEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aisb_get_global_settings',
                    nonce: aisbEditor.aisbNonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentSettings = response.data.settings;
                        self.originalSettings = JSON.parse(JSON.stringify(response.data.settings));
                        self.populateFields();
                        self.isDirty = false;
                    }
                },
                error: function() {
                    console.error('Failed to load global settings');
                }
            });
        },
        
        /**
         * Populate form fields with current settings
         */
        populateFields: function() {
            var settings = this.currentSettings;
            
            // Colors
            if (settings.colors) {
                $.each(settings.colors, function(key, value) {
                    $('#aisb-gs-' + key.replace(/_/g, '-')).val(value);
                    $('#aisb-gs-' + key.replace(/_/g, '-')).siblings('.aisb-color-text').val(value);
                });
            }
            
            // Typography
            if (settings.typography) {
                $('#aisb-gs-font-heading').val(settings.typography.font_heading);
                $('#aisb-gs-font-body').val(settings.typography.font_body);
                $('#aisb-gs-size-base').val(settings.typography.size_base);
                $('#aisb-gs-scale-ratio').val(settings.typography.scale_ratio);
                $('#aisb-gs-line-height-body').val(settings.typography.line_height_body);
                $('#aisb-gs-line-height-heading').val(settings.typography.line_height_heading);
            }
            
            // Layout
            if (settings.layout) {
                $('#aisb-gs-container-width').val(settings.layout.container_width);
                $('#aisb-gs-section-padding').val(settings.layout.section_padding);
                $('#aisb-gs-element-spacing').val(settings.layout.element_spacing);
                $('#aisb-gs-breakpoint-tablet').val(settings.layout.breakpoint_tablet);
                $('#aisb-gs-breakpoint-mobile').val(settings.layout.breakpoint_mobile);
            }
        },
        
        /**
         * Gather settings from form
         */
        gatherSettings: function() {
            return {
                colors: {
                    base: $('#aisb-gs-base').val(),
                    text: $('#aisb-gs-text').val(),
                    muted: $('#aisb-gs-muted').val(),
                    primary: $('#aisb-gs-primary').val(),
                    primary_hover: $('#aisb-gs-primary-hover').val(),
                    secondary: $('#aisb-gs-secondary').val(),
                    border: $('#aisb-gs-border').val(),
                    success: $('#aisb-gs-success').val(),
                    error: $('#aisb-gs-error').val(),
                    dark_base: $('#aisb-gs-dark-base').val(),
                    dark_text: $('#aisb-gs-dark-text').val(),
                    dark_muted: $('#aisb-gs-dark-muted').val(),
                    dark_primary: $('#aisb-gs-dark-primary').val(),
                    dark_primary_hover: $('#aisb-gs-dark-primary-hover').val(),
                    dark_secondary: $('#aisb-gs-dark-secondary').val(),
                    dark_border: $('#aisb-gs-dark-border').val()
                },
                typography: {
                    font_heading: $('#aisb-gs-font-heading').val(),
                    font_body: $('#aisb-gs-font-body').val(),
                    size_base: parseFloat($('#aisb-gs-size-base').val()),
                    scale_ratio: parseFloat($('#aisb-gs-scale-ratio').val()),
                    line_height_body: parseFloat($('#aisb-gs-line-height-body').val()),
                    line_height_heading: parseFloat($('#aisb-gs-line-height-heading').val())
                },
                layout: {
                    container_width: parseInt($('#aisb-gs-container-width').val()),
                    section_padding: parseInt($('#aisb-gs-section-padding').val()),
                    element_spacing: parseInt($('#aisb-gs-element-spacing').val()),
                    breakpoint_tablet: parseInt($('#aisb-gs-breakpoint-tablet').val()),
                    breakpoint_mobile: parseInt($('#aisb-gs-breakpoint-mobile').val())
                }
            };
        },
        
        /**
         * Save settings to server
         */
        saveSettings: function() {
            var self = this;
            var $saveBtn = $('#aisb-save-global-settings');
            
            // Disable button and show loading
            $saveBtn.prop('disabled', true).addClass('aisb-btn-loading');
            
            var settings = this.gatherSettings();
            
            $.ajax({
                url: aisbEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aisb_save_global_settings',
                    nonce: aisbEditor.aisbNonce,
                    settings: JSON.stringify(settings)
                },
                success: function(response) {
                    if (response.success) {
                        self.currentSettings = settings;
                        self.originalSettings = JSON.parse(JSON.stringify(settings));
                        self.isDirty = false;
                        self.showNotification('Settings saved successfully', 'success');
                        
                        // Reload page to apply new CSS variables
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotification(response.data || 'Failed to save settings', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Network error. Please try again.', 'error');
                },
                complete: function() {
                    $saveBtn.prop('disabled', false).removeClass('aisb-btn-loading');
                }
            });
        },
        
        /**
         * Reset settings to defaults
         */
        resetSettings: function() {
            var self = this;
            
            if (!confirm('This will reset all global settings to their default values. Are you sure?')) {
                return;
            }
            
            $.ajax({
                url: aisbEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aisb_reset_global_settings',
                    nonce: aisbEditor.aisbNonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentSettings = response.data.settings;
                        self.originalSettings = JSON.parse(JSON.stringify(response.data.settings));
                        self.populateFields();
                        self.isDirty = false;
                        self.showNotification('Settings reset to defaults', 'success');
                        self.updatePreview();
                    }
                },
                error: function() {
                    self.showNotification('Failed to reset settings', 'error');
                }
            });
        },
        
        /**
         * Update live preview
         */
        updatePreview: function() {
            var settings = this.gatherSettings();
            var root = document.documentElement;
            
            // Update CSS variables in real-time - Override core design tokens
            root.style.setProperty('--aisb-color-base', settings.colors.base);
            root.style.setProperty('--aisb-color-text', settings.colors.text);
            root.style.setProperty('--aisb-color-muted', settings.colors.muted);
            root.style.setProperty('--aisb-color-primary', settings.colors.primary);
            root.style.setProperty('--aisb-color-primary-hover', settings.colors.primary_hover);
            root.style.setProperty('--aisb-color-secondary', settings.colors.secondary);
            root.style.setProperty('--aisb-color-border', settings.colors.border);
            root.style.setProperty('--aisb-color-success', settings.colors.success);
            root.style.setProperty('--aisb-color-error', settings.colors.error);
            root.style.setProperty('--aisb-color-dark-base', settings.colors.dark_base);
            root.style.setProperty('--aisb-color-dark-text', settings.colors.dark_text);
            root.style.setProperty('--aisb-color-dark-muted', settings.colors.dark_muted);
            root.style.setProperty('--aisb-color-dark-primary', settings.colors.dark_primary);
            root.style.setProperty('--aisb-color-dark-primary-hover', settings.colors.dark_primary_hover);
            root.style.setProperty('--aisb-color-dark-secondary', settings.colors.dark_secondary);
            root.style.setProperty('--aisb-color-dark-border', settings.colors.dark_border);
            
            root.style.setProperty('--aisb-gs-font-heading', settings.typography.font_heading);
            root.style.setProperty('--aisb-gs-font-body', settings.typography.font_body);
            root.style.setProperty('--aisb-gs-size-base', settings.typography.size_base + 'px');
            root.style.setProperty('--aisb-gs-scale-ratio', settings.typography.scale_ratio);
            root.style.setProperty('--aisb-gs-line-height-body', settings.typography.line_height_body);
            root.style.setProperty('--aisb-gs-line-height-heading', settings.typography.line_height_heading);
            
            root.style.setProperty('--aisb-gs-container-width', settings.layout.container_width + 'px');
            root.style.setProperty('--aisb-gs-section-padding', settings.layout.section_padding + 'px');
            root.style.setProperty('--aisb-gs-element-spacing', settings.layout.element_spacing + 'px');
            
            // Calculate heading sizes
            var baseSize = settings.typography.size_base;
            var scale = settings.typography.scale_ratio;
            
            root.style.setProperty('--aisb-gs-size-h6', Math.round(baseSize * scale) + 'px');
            root.style.setProperty('--aisb-gs-size-h5', Math.round(baseSize * scale * scale) + 'px');
            root.style.setProperty('--aisb-gs-size-h4', Math.round(baseSize * scale * scale * scale) + 'px');
            root.style.setProperty('--aisb-gs-size-h3', Math.round(baseSize * scale * scale * scale * scale) + 'px');
            root.style.setProperty('--aisb-gs-size-h2', Math.round(baseSize * scale * scale * scale * scale * scale) + 'px');
            root.style.setProperty('--aisb-gs-size-h1', Math.round(baseSize * scale * scale * scale * scale * scale * scale) + 'px');
        },
        
        /**
         * Restore original settings
         */
        restoreOriginalSettings: function() {
            this.currentSettings = JSON.parse(JSON.stringify(this.originalSettings));
            this.populateFields();
            this.updatePreview();
            this.isDirty = false;
        },
        
        /**
         * Validate number input
         */
        validateNumberInput: function($input) {
            var value = parseFloat($input.val());
            var min = parseFloat($input.attr('min'));
            var max = parseFloat($input.attr('max'));
            
            if (value < min) {
                $input.val(min);
            } else if (value > max) {
                $input.val(max);
            }
        },
        
        /**
         * Mark as dirty
         */
        markDirty: function() {
            this.isDirty = true;
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Create notification element
            var $notification = $('<div class="aisb-notification aisb-notification-' + type + '">' + message + '</div>');
            
            // Add to body
            $('body').append($notification);
            
            // Position and show
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '12px 20px',
                background: type === 'success' ? '#10b981' : '#ef4444',
                color: '#ffffff',
                borderRadius: '4px',
                boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                zIndex: 100001,
                opacity: 0,
                transform: 'translateY(-10px)'
            });
            
            // Animate in
            $notification.animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, 200);
            
            // Remove after delay
            setTimeout(function() {
                $notification.animate({
                    opacity: 0,
                    transform: 'translateY(-10px)'
                }, 200, function() {
                    $notification.remove();
                });
            }, 3000);
        }
    };
    
    // Initialize when ready
    $(document).ready(function() {
        GlobalSettings.init();
    });
    
})(jQuery);
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
            // Ensure initial panel visibility state
            this.initializePanels();
            this.bindEvents();
            this.loadCurrentSettings();
        },
        
        /**
         * Initialize panel visibility
         */
        initializePanels: function() {
            // Ensure sections panel is shown initially
            $('.aisb-panel-content').hide();
            $('#aisb-panel-sections').show().addClass('active');
            
            // Ensure sections tab is active
            $('.aisb-panel-tab').removeClass('active');
            $('#aisb-tab-sections').addClass('active');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Main panel tab switching (Sections vs Settings)
            $(document).on('click', '.aisb-panel-tab', function(e) {
                e.preventDefault();
                var panel = $(this).data('panel');
                self.switchMainPanel(panel);
            });
            
            // Settings sub-tab switching (Colors, Typography, Layout)
            $(document).on('click', '.aisb-settings-tab', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                self.switchSettingsTab(tab);
            });
            
            // Global settings button in toolbar
            $(document).on('click', '#aisb-global-settings-btn, #aisb-tab-settings', function(e) {
                e.preventDefault();
                self.openSettingsPanel();
            });
            
            // Color input sync - use delegated events
            $(document).on('input', '.aisb-color-input-wrapper input[type="color"]', function() {
                var $textInput = $(this).siblings('.aisb-color-text');
                $textInput.val($(this).val());
                self.updatePreview();
                self.markDirty();
            });
            
            $(document).on('input', '.aisb-color-input-wrapper .aisb-color-text', function() {
                var $colorInput = $(this).siblings('input[type="color"]');
                var value = $(this).val();
                
                // Validate hex color
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    $colorInput.val(value);
                    self.updatePreview();
                    self.markDirty();
                }
            });
            
            // Number input changes - use delegated events
            $(document).on('input', '.aisb-settings-content input[type="number"]', function() {
                self.validateNumberInput($(this));
                self.updatePreview();
                self.markDirty();
            });
            
            // Text input changes - use delegated events
            $(document).on('input', '.aisb-settings-content input[type="text"]:not(.aisb-color-text)', function() {
                self.updatePreview();
                self.markDirty();
            });
            
            // Save settings
            $(document).on('click', '#aisb-save-global-settings', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
            
            // Reset settings
            $(document).on('click', '#aisb-reset-global-settings', function(e) {
                e.preventDefault();
                self.resetSettings();
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Cmd/Ctrl + , to open settings
                if ((e.metaKey || e.ctrlKey) && e.key === ',') {
                    e.preventDefault();
                    self.openSettingsPanel();
                }
            });
        },
        
        /**
         * Open settings panel
         */
        openSettingsPanel: function() {
            // Switch to settings tab
            $('.aisb-panel-tab').removeClass('active');
            $('#aisb-tab-settings').addClass('active');
            
            // Hide all panels and show settings panel
            $('.aisb-panel-content').removeClass('active').hide();
            $('#aisb-panel-settings').addClass('active').show();
            
            // Load settings if not already loaded
            this.loadCurrentSettings();
        },
        
        /**
         * Switch main panel (Sections vs Settings)
         */
        switchMainPanel: function(panel) {
            // Update tab buttons
            $('.aisb-panel-tab').removeClass('active');
            $('.aisb-panel-tab[data-panel="' + panel + '"]').addClass('active');
            
            // Hide all panels first
            $('.aisb-panel-content').removeClass('active').hide();
            
            // Show the selected panel
            $('#aisb-panel-' + panel).addClass('active').show();
            
            // Load settings when switching to settings panel
            if (panel === 'settings') {
                this.loadCurrentSettings();
            }
        },
        
        /**
         * Switch settings sub-tabs
         */
        switchSettingsTab: function(tab) {
            // Update tab buttons
            $('.aisb-settings-tab').removeClass('active');
            $('.aisb-settings-tab[data-tab="' + tab + '"]').addClass('active');
            
            // Update tab content
            $('.aisb-settings-panel').removeClass('active');
            $('.aisb-settings-panel[data-panel="' + tab + '"]').addClass('active');
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
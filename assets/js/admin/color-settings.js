/**
 * AI Section Builder - Color Settings Manager
 * Handles primary color setting save/reset functionality
 * 
 * @package AISB
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    class AISBColorSettings {
        constructor() {
            this.debounceTimer = null;
            this.hasUnsavedChanges = false;
            this.originalValues = {};
            this.init();
        }
        
        init() {
            // Wait for DOM ready
            $(document).ready(() => {
                this.storeOriginalValues();
                this.setupColorPickers();
                this.bindEvents();
                
                // Expose this instance globally for integration with main save
                window.aisbGlobalSettings = this;
            });
        }
        
        storeOriginalValues() {
            this.originalValues = {
                primary: $('#aisb-gs-primary').val() || '',
                textLight: $('#aisb-gs-text-light').val() || '',
                textDark: $('#aisb-gs-text-dark').val() || '',
                secondaryLight: $('#aisb-gs-secondary-light').val() || '',
                secondaryDark: $('#aisb-gs-secondary-dark').val() || '',
                borderLight: $('#aisb-gs-border-light').val() || '',
                borderDark: $('#aisb-gs-border-dark').val() || ''
            };
        }
        
        markAsChanged() {
            this.hasUnsavedChanges = true;
            
            // If main editor exists, mark it as dirty too
            if (window.editorState) {
                window.editorState.isDirty = true;
                window.editorState.globalSettingsDirty = true;
                
                // Update main save button status
                if (typeof window.updateSaveStatus === 'function') {
                    window.updateSaveStatus('unsaved');
                }
            }
        }
        
        markAsSaved() {
            this.hasUnsavedChanges = false;
            this.storeOriginalValues(); // Update original values after save
            
            // Update main editor state
            if (window.editorState) {
                window.editorState.globalSettingsDirty = false;
            }
        }
        
        /**
         * Debounce function to limit rapid-fire updates
         */
        debounce(func, delay = 150) {
            return (...args) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => func.apply(this, args), delay);
            };
        }
        
        setupColorPickers() {
            // Real-time preview on primary color picker
            $('#aisb-gs-primary').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({primary: color}), 150)();
            });
            
            // Real-time preview on text light color picker
            $('#aisb-gs-text-light').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({textLight: color}), 150)();
            });
            
            // Real-time preview on text dark color picker
            $('#aisb-gs-text-dark').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({textDark: color}), 150)();
            });
            
            // Real-time preview on secondary light color picker
            $('#aisb-gs-secondary-light').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({secondaryLight: color}), 150)();
            });
            
            // Real-time preview on secondary dark color picker
            $('#aisb-gs-secondary-dark').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({secondaryDark: color}), 150)();
            });
            
            // Real-time preview on border light color picker
            $('#aisb-gs-border-light').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({borderLight: color}), 150)();
            });
            
            // Real-time preview on border dark color picker
            $('#aisb-gs-border-dark').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Mark as changed
                this.markAsChanged();
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({borderDark: color}), 150)();
            });
            
            // Handle text input changes with validation for all color fields
            $('.aisb-settings-field').find('.aisb-color-text').on('input', (e) => {
                const value = $(e.target).val();
                const $colorPicker = $(e.target).siblings('input[type="color"]');
                
                // Validate hex color format
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    // Sync with color picker
                    $colorPicker.val(value);
                    
                    // Mark as changed
                    this.markAsChanged();
                    
                    // Determine which color was changed and update preview
                    const pickerId = $colorPicker.attr('id');
                    let updateData = {};
                    
                    if (pickerId === 'aisb-gs-primary') {
                        updateData.primary = value;
                    } else if (pickerId === 'aisb-gs-text-light') {
                        updateData.textLight = value;
                    } else if (pickerId === 'aisb-gs-text-dark') {
                        updateData.textDark = value;
                    } else if (pickerId === 'aisb-gs-secondary-light') {
                        updateData.secondaryLight = value;
                    } else if (pickerId === 'aisb-gs-secondary-dark') {
                        updateData.secondaryDark = value;
                    } else if (pickerId === 'aisb-gs-border-light') {
                        updateData.borderLight = value;
                    } else if (pickerId === 'aisb-gs-border-dark') {
                        updateData.borderDark = value;
                    }
                    
                    // Update preview (debounced)
                    this.debounce(() => this.updatePreview(updateData), 150)();
                }
            });
        }
        
        bindEvents() {
            // Reset button only - save is handled by main save button
            $('#aisb-reset-global-settings').on('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to reset to default colors?')) {
                    this.resetPrimaryColor();
                }
            });
        }
        
        savePrimaryColor() {
            const primaryColor = $('#aisb-gs-primary').val();
            const textLightColor = $('#aisb-gs-text-light').val() || '#1a1a1a';  // Default if field doesn't exist
            const textDarkColor = $('#aisb-gs-text-dark').val() || '#fafafa';    // Default if field doesn't exist
            const secondaryLightColor = $('#aisb-gs-secondary-light').val() || '#f1f5f9';
            const secondaryDarkColor = $('#aisb-gs-secondary-dark').val() || '#374151';
            const borderLightColor = $('#aisb-gs-border-light').val() || '#e2e8f0';
            const borderDarkColor = $('#aisb-gs-border-dark').val() || '#4b5563';
            
            // Show loading state
            this.showMessage('Saving colors...', 'info');
            
            // Single unified save request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_save_all_colors',
                    primary_color: primaryColor,
                    text_light_color: textLightColor,
                    text_dark_color: textDarkColor,
                    secondary_light_color: secondaryLightColor,
                    secondary_dark_color: secondaryDarkColor,
                    border_light_color: borderLightColor,
                    border_dark_color: borderDarkColor,
                    nonce: this.getNonce()
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('All colors saved successfully', 'success');
                        // Mark as saved
                        this.markAsSaved();
                        // Update preview without indicator (already saved)
                        this.updatePreview({
                            primary: response.data.colors.primary,
                            textLight: response.data.colors.text_light,
                            textDark: response.data.colors.text_dark,
                            secondaryLight: response.data.colors.secondary_light,
                            secondaryDark: response.data.colors.secondary_dark,
                            borderLight: response.data.colors.border_light,
                            borderDark: response.data.colors.border_dark
                        }, false);
                    } else {
                        this.showMessage(response.data?.message || 'Failed to save colors', 'error');
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.showMessage('Network error. Please try again.', 'error');
                }
            });
        }
        
        resetPrimaryColor() {
            // Define default colors
            const defaults = {
                primary: '#2563eb',
                textLight: '#1a1a1a',
                textDark: '#fafafa',
                secondaryLight: '#f1f5f9',
                secondaryDark: '#374151',
                borderLight: '#e2e8f0',
                borderDark: '#4b5563'
            };
            
            // Update all color pickers to defaults
            $('#aisb-gs-primary').val(defaults.primary).trigger('input');
            $('#aisb-gs-primary').siblings('.aisb-color-text').val(defaults.primary);
            
            $('#aisb-gs-text-light').val(defaults.textLight).trigger('input');
            $('#aisb-gs-text-light').siblings('.aisb-color-text').val(defaults.textLight);
            
            $('#aisb-gs-text-dark').val(defaults.textDark).trigger('input');
            $('#aisb-gs-text-dark').siblings('.aisb-color-text').val(defaults.textDark);
            
            $('#aisb-gs-secondary-light').val(defaults.secondaryLight).trigger('input');
            $('#aisb-gs-secondary-light').siblings('.aisb-color-text').val(defaults.secondaryLight);
            
            $('#aisb-gs-secondary-dark').val(defaults.secondaryDark).trigger('input');
            $('#aisb-gs-secondary-dark').siblings('.aisb-color-text').val(defaults.secondaryDark);
            
            $('#aisb-gs-border-light').val(defaults.borderLight).trigger('input');
            $('#aisb-gs-border-light').siblings('.aisb-color-text').val(defaults.borderLight);
            
            $('#aisb-gs-border-dark').val(defaults.borderDark).trigger('input');
            $('#aisb-gs-border-dark').siblings('.aisb-color-text').val(defaults.borderDark);
            
            // Mark as changed so user can save
            this.markAsChanged();
            
            // Update preview with indicator showing changes need to be saved
            this.updatePreview({
                primary: defaults.primary,
                textLight: defaults.textLight,
                textDark: defaults.textDark,
                secondaryLight: defaults.secondaryLight,
                secondaryDark: defaults.secondaryDark,
                borderLight: defaults.borderLight,
                borderDark: defaults.borderDark
            }, true);
            
            // Show message
            this.showMessage('Colors reset to defaults - Click Save to apply changes', 'info');
        }
        
        updatePreview(colorsOrString, showIndicator = true) {
            // Update CSS variables in real-time in the current document
            const $style = $('#aisb-color-settings');
            
            if ($style.length) {
                let currentCSS = $style.html();
                let colors = {};
                
                // Handle both string (backward compatibility) and object (new format)
                if (typeof colorsOrString === 'string') {
                    // Old format: single color string for primary
                    colors.primary = colorsOrString;
                } else {
                    // New format: object with multiple colors
                    colors = colorsOrString;
                }
                
                // Update primary color if provided
                if (colors.primary) {
                    const color = colors.primary;
                    const hoverColor = this.darkenColor(color, 10);
                    const darkColor = this.lightenColor(color, 20);
                    const darkHoverColor = this.lightenColor(color, 30);
                    
                    currentCSS = currentCSS
                        .replace(/--aisb-color-primary:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-primary: ' + color)
                        .replace(/--aisb-color-primary-hover:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-primary-hover: ' + hoverColor)
                        .replace(/--aisb-color-dark-primary:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-dark-primary: ' + darkColor)
                        .replace(/--aisb-color-dark-primary-hover:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-dark-primary-hover: ' + darkHoverColor)
                        .replace(/--aisb-interactive-primary:\s*#[0-9a-fA-F]{6}/g, '--aisb-interactive-primary: ' + color)
                        .replace(/--aisb-interactive-primary-hover:\s*#[0-9a-fA-F]{6}/g, '--aisb-interactive-primary-hover: ' + hoverColor)
                        .replace(/--aisb-interactive-secondary:\s*#[0-9a-fA-F]{6}/g, '--aisb-interactive-secondary: ' + color)
                        .replace(/--aisb-interactive-secondary-text:\s*#[0-9a-fA-F]{6}/g, '--aisb-interactive-secondary-text: ' + color)
                        .replace(/--aisb-content-link:\s*#[0-9a-fA-F]{6}/g, '--aisb-content-link: ' + color)
                        .replace(/--aisb-content-link-hover:\s*#[0-9a-fA-F]{6}/g, '--aisb-content-link-hover: ' + hoverColor)
                        .replace(/--aisb-border-interactive:\s*#[0-9a-fA-F]{6}/g, '--aisb-border-interactive: ' + color)
                        .replace(/--aisb-feedback-info:\s*#[0-9a-fA-F]{6}/g, '--aisb-feedback-info: ' + color);
                }
                
                // Update text light color if provided
                if (colors.textLight) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-text:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-text: ' + colors.textLight)
                        .replace(/--aisb-content-primary:\s*#[0-9a-fA-F]{6}/g, '--aisb-content-primary: ' + colors.textLight);
                }
                
                // Update text dark color if provided
                if (colors.textDark) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-dark-text:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-dark-text: ' + colors.textDark);
                }
                
                // Update secondary light color if provided
                if (colors.secondaryLight) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-secondary:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-secondary: ' + colors.secondaryLight)
                        .replace(/--aisb-surface-secondary:\s*#[0-9a-fA-F]{6}/g, '--aisb-surface-secondary: ' + colors.secondaryLight);
                }
                
                // Update secondary dark color if provided
                if (colors.secondaryDark) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-dark-secondary:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-dark-secondary: ' + colors.secondaryDark);
                }
                
                // Update border light color if provided
                if (colors.borderLight) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-border:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-border: ' + colors.borderLight)
                        .replace(/--aisb-border-primary:\s*#[0-9a-fA-F]{6}/g, '--aisb-border-primary: ' + colors.borderLight)
                        .replace(/--aisb-border-secondary:\s*#[0-9a-fA-F]{6}/g, '--aisb-border-secondary: ' + colors.borderLight);
                }
                
                // Update border dark color if provided
                if (colors.borderDark) {
                    currentCSS = currentCSS
                        .replace(/--aisb-color-dark-border:\s*#[0-9a-fA-F]{6}/g, '--aisb-color-dark-border: ' + colors.borderDark);
                }
                
                $style.html(currentCSS);
                
                // Show a subtle indicator that preview is active (not saved)
                if (showIndicator) {
                    this.showPreviewIndicator();
                }
            }
        }
        
        showPreviewIndicator() {
            // Remove existing preview indicators
            $('.aisb-preview-indicator').remove();
            
            // Add subtle indicator near the color field
            const $indicator = $('<span class="aisb-preview-indicator" style="margin-left: 10px; color: #f59e0b; font-size: 12px; font-style: italic;">Preview (not saved)</span>');
            $('#aisb-gs-primary').parent().append($indicator);
            
            // Clear indicator after 2 seconds
            setTimeout(() => {
                $indicator.fadeOut(() => $indicator.remove());
            }, 2000);
        }
        
        // Helper function to darken color
        darkenColor(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.max((num >> 16) - amt, 0);
            const G = Math.max((num >> 8 & 0x00FF) - amt, 0);
            const B = Math.max((num & 0x0000FF) - amt, 0);
            return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
        }
        
        // Helper function to lighten color
        lightenColor(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.min((num >> 16) + amt, 255);
            const G = Math.min((num >> 8 & 0x00FF) + amt, 255);
            const B = Math.min((num & 0x0000FF) + amt, 255);
            return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
        }
        
        getNonce() {
            // Try to get nonce from localized script or hidden field
            return window.aisbColorSettings?.nonce || $('#aisb-color-nonce').val() || '';
        }
        
        showMessage(message, type) {
            // Remove existing messages
            $('.aisb-settings-message').remove();
            
            // Create message element
            const messageClass = type === 'success' ? 'aisb-message-success' : 
                               type === 'error' ? 'aisb-message-error' : 
                               'aisb-message-info';
            
            const $message = $('<div>')
                .addClass('aisb-settings-message ' + messageClass)
                .text(message);
            
            // Insert after settings header
            $('.aisb-settings-content').prepend($message);
            
            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut(() => $message.remove());
                }, 3000);
            }
        }
    }
    
    // Initialize
    new AISBColorSettings();
    
})(jQuery);
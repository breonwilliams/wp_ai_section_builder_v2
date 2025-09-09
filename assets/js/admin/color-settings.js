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
            this.init();
        }
        
        init() {
            // Wait for DOM ready
            $(document).ready(() => {
                this.setupColorPickers();
                this.bindEvents();
            });
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
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({primary: color}), 150)();
            });
            
            // Real-time preview on text light color picker
            $('#aisb-gs-text-light').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({textLight: color}), 150)();
            });
            
            // Real-time preview on text dark color picker
            $('#aisb-gs-text-dark').on('input', (e) => {
                const color = $(e.target).val();
                // Sync with text input
                $(e.target).siblings('.aisb-color-text').val(color);
                // Update preview immediately (debounced)
                this.debounce(() => this.updatePreview({textDark: color}), 150)();
            });
            
            // Handle text input changes with validation for all color fields
            $('.aisb-settings-field').find('.aisb-color-text').on('input', (e) => {
                const value = $(e.target).val();
                const $colorPicker = $(e.target).siblings('input[type="color"]');
                
                // Validate hex color format
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    // Sync with color picker
                    $colorPicker.val(value);
                    
                    // Determine which color was changed and update preview
                    const pickerId = $colorPicker.attr('id');
                    let updateData = {};
                    
                    if (pickerId === 'aisb-gs-primary') {
                        updateData.primary = value;
                    } else if (pickerId === 'aisb-gs-text-light') {
                        updateData.textLight = value;
                    } else if (pickerId === 'aisb-gs-text-dark') {
                        updateData.textDark = value;
                    }
                    
                    // Update preview (debounced)
                    this.debounce(() => this.updatePreview(updateData), 150)();
                }
            });
        }
        
        bindEvents() {
            // Save button
            $('#aisb-save-global-settings').on('click', (e) => {
                e.preventDefault();
                this.savePrimaryColor();
            });
            
            // Reset button
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
                    nonce: this.getNonce()
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('All colors saved successfully', 'success');
                        // Update preview without indicator (already saved)
                        this.updatePreview({
                            primary: response.data.colors.primary,
                            textLight: response.data.colors.text_light,
                            textDark: response.data.colors.text_dark
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
            // Show loading state
            this.showMessage('Resetting colors...', 'info');
            
            // Reset primary color
            const resetPrimary = $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_reset_primary_color',
                    nonce: this.getNonce()
                }
            });
            
            // Reset text colors
            const resetText = $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_reset_text_colors',
                    nonce: this.getNonce()
                }
            });
            
            // Wait for all resets to complete
            $.when(resetPrimary, resetText).done((primaryResponse, textResponse) => {
                if (primaryResponse[0].success && textResponse[0].success) {
                    this.showMessage('All colors reset to defaults', 'success');
                    
                    // Update color pickers to defaults
                    $('#aisb-gs-primary').val(primaryResponse[0].data.color);
                    $('#aisb-gs-primary').siblings('.aisb-color-text').val(primaryResponse[0].data.color);
                    
                    $('#aisb-gs-text-light').val(textResponse[0].data.text_light);
                    $('#aisb-gs-text-light').siblings('.aisb-color-text').val(textResponse[0].data.text_light);
                    
                    $('#aisb-gs-text-dark').val(textResponse[0].data.text_dark);
                    $('#aisb-gs-text-dark').siblings('.aisb-color-text').val(textResponse[0].data.text_dark);
                    
                    // Update preview without indicator (already saved)
                    this.updatePreview({
                        primary: primaryResponse[0].data.color,
                        textLight: textResponse[0].data.text_light,
                        textDark: textResponse[0].data.text_dark
                    }, false);
                } else {
                    this.showMessage('Some colors failed to reset', 'error');
                }
            }).fail(() => {
                this.showMessage('Network error. Please try again.', 'error');
            });
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
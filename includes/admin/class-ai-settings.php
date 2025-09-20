<?php
/**
 * AI Settings Admin Page
 * 
 * @package AI_Section_Builder
 * @since 1.0.0
 */

namespace AISB\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Settings class for handling the AI configuration page
 */
class AI_Settings {

    /**
     * Render the AI Settings page
     */
    public function render() {
        // Get current settings
        $settings = get_option('aisb_ai_settings', array(
            'provider' => '',
            'api_key' => '',
            'model' => '',
            'verified' => false,
            'last_verified' => 0
        ));
        
        // Decrypt API key for display (show last 4 characters only)
        $api_key_display = '';
        if (!empty($settings['api_key'])) {
            $decrypted = aisb_decrypt_api_key($settings['api_key']);
            if ($decrypted && strlen($decrypted) > 4) {
                $api_key_display = str_repeat('•', strlen($decrypted) - 4) . substr($decrypted, -4);
            }
        }
        
        // Model options based on provider
        $model_options = array(
            'openai' => array(
                'gpt-4' => 'GPT-4',
                'gpt-4-turbo-preview' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
            ),
            'anthropic' => array(
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
                'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku'
            )
        );
        ?>
        <div class="wrap">
            <h1><?php _e('AI Settings', 'ai-section-builder'); ?></h1>
            
            <div class="notice notice-info" style="margin-top: 20px;">
                <p><?php _e('Configure your AI provider settings to enable content generation features.', 'ai-section-builder'); ?></p>
                <p><strong><?php _e('Setup Steps:', 'ai-section-builder'); ?></strong></p>
                <ol style="margin: 0 0 0 20px;">
                    <li><?php _e('Select your AI provider (OpenAI or Anthropic)', 'ai-section-builder'); ?></li>
                    <li><?php _e('Enter your API key', 'ai-section-builder'); ?></li>
                    <li><?php _e('Choose a model', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Test Connection" to verify', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Save Settings" once verified', 'ai-section-builder'); ?></li>
                </ol>
            </div>
            
            <form method="post" id="aisb-ai-settings-form">
                <?php wp_nonce_field('aisb_ai_settings', 'aisb_ai_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('AI Provider', 'ai-section-builder'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="aisb_provider" value="openai" <?php checked(isset($settings['provider']) ? $settings['provider'] : '', 'openai'); ?> />
                                    <span><?php _e('OpenAI', 'ai-section-builder'); ?></span>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="aisb_provider" value="anthropic" <?php checked(isset($settings['provider']) ? $settings['provider'] : '', 'anthropic'); ?> />
                                    <span><?php _e('Anthropic (Claude)', 'ai-section-builder'); ?></span>
                                </label>
                            </fieldset>
                            <p class="description"><?php _e('Select your preferred AI service provider.', 'ai-section-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="aisb_api_key"><?php _e('API Key', 'ai-section-builder'); ?></label>
                        </th>
                        <td>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="password" 
                                       id="aisb_api_key" 
                                       name="aisb_api_key" 
                                       class="regular-text" 
                                       placeholder="<?php echo esc_attr($api_key_display ?: __('Enter your API key', 'ai-section-builder')); ?>"
                                       autocomplete="off" 
                                       data-has-saved-key="<?php echo !empty($settings['api_key']) ? 'true' : 'false'; ?>" />
                                <input type="hidden" 
                                       id="aisb_keep_existing_key" 
                                       name="aisb_keep_existing_key" 
                                       value="<?php echo !empty($settings['api_key']) ? '1' : '0'; ?>" />
                                <button type="button" 
                                        id="aisb-toggle-api-key" 
                                        class="button button-secondary">
                                    <?php _e('Show', 'ai-section-builder'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Your API key will be encrypted before storage.', 'ai-section-builder'); ?>
                                <span id="aisb-provider-help" style="display: none;">
                                    <br>
                                    <span class="openai-help" style="display: none;">
                                        <?php printf(__('Get your API key from %s', 'ai-section-builder'), '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'); ?>
                                    </span>
                                    <span class="anthropic-help" style="display: none;">
                                        <?php printf(__('Get your API key from %s', 'ai-section-builder'), '<a href="https://console.anthropic.com/api-keys" target="_blank">Anthropic Console</a>'); ?>
                                    </span>
                                </span>
                            </p>
                        </td>
                    </tr>
                    
                    <tr id="aisb-model-row" style="<?php echo empty($settings['provider']) ? 'display: none;' : ''; ?>">
                        <th scope="row">
                            <label for="aisb_model"><?php _e('Model', 'ai-section-builder'); ?></label>
                        </th>
                        <td>
                            <select id="aisb_model" name="aisb_model" class="regular-text">
                                <option value=""><?php _e('Select a model', 'ai-section-builder'); ?></option>
                                <?php
                                if (!empty($settings['provider']) && isset($model_options[$settings['provider']])) {
                                    foreach ($model_options[$settings['provider']] as $value => $label) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($value),
                                            selected($settings['model'], $value, false),
                                            esc_html($label)
                                        );
                                    }
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the AI model to use for content generation.', 'ai-section-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Connection Status', 'ai-section-builder'); ?></th>
                        <td>
                            <div id="aisb-connection-status">
                                <?php 
                                // Only show connected if we have all required settings
                                $is_connected = !empty($settings['verified']) && 
                                              !empty($settings['provider']) && 
                                              !empty($settings['api_key']) &&
                                              !empty($settings['model']);
                                
                                if ($is_connected): ?>
                                    <span style="color: #00a32a;">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Connected', 'ai-section-builder'); ?>
                                        <?php if (!empty($settings['last_verified'])): ?>
                                            <small>(<?php printf(__('Last verified: %s', 'ai-section-builder'), human_time_diff($settings['last_verified'], current_time('timestamp')) . ' ago'); ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">
                                        <span class="dashicons dashicons-minus"></span>
                                        <?php _e('Not connected', 'ai-section-builder'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" 
                            id="aisb-test-connection" 
                            class="button button-secondary"
                            <?php echo empty($settings['provider']) ? 'disabled' : ''; ?>>
                        <?php _e('Test Connection', 'ai-section-builder'); ?>
                    </button>
                    <button type="submit" 
                            name="submit" 
                            id="submit" 
                            class="button button-primary">
                        <?php _e('Save Settings', 'ai-section-builder'); ?>
                    </button>
                    <span class="spinner" style="float: none;"></span>
                </p>
            </form>
            
            <div id="aisb-settings-message" style="display: none; margin-top: 20px;"></div>
        </div>
        
        <?php
        // Enqueue the JavaScript
        $this->enqueue_scripts($model_options, $is_connected);
    }

    /**
     * Enqueue JavaScript for the AI Settings page
     * 
     * @param array $model_options Array of model options for each provider
     * @param bool $is_connected Whether the connection is currently verified
     */
    public function enqueue_scripts($model_options, $is_connected) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Model options data
            var modelOptions = <?php echo json_encode($model_options); ?>;
            // Check if already verified from PHP
            var connectionVerified = <?php echo ($is_connected ? 'true' : 'false'); ?>;
            
            // Toggle API key visibility
            $('#aisb-toggle-api-key').on('click', function() {
                var $input = $('#aisb_api_key');
                var $button = $(this);
                
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $button.text('<?php _e('Hide', 'ai-section-builder'); ?>');
                } else {
                    $input.attr('type', 'password');
                    $button.text('<?php _e('Show', 'ai-section-builder'); ?>');
                }
            });
            
            // Update model dropdown and help text when provider changes
            $('input[name="aisb_provider"]').on('change', function() {
                var provider = $(this).val();
                var $modelSelect = $('#aisb_model');
                var $modelRow = $('#aisb-model-row');
                var $testButton = $('#aisb-test-connection');
                var $providerHelp = $('#aisb-provider-help');
                
                // Clear and update model dropdown
                $modelSelect.empty().append('<option value=""><?php _e('Select a model', 'ai-section-builder'); ?></option>');
                
                if (provider && modelOptions[provider]) {
                    $modelRow.show();
                    $testButton.prop('disabled', false);
                    
                    $.each(modelOptions[provider], function(value, label) {
                        $modelSelect.append($('<option>', {
                            value: value,
                            text: label
                        }));
                    });
                    
                    // Show appropriate help text
                    $providerHelp.show();
                    $('.openai-help, .anthropic-help').hide();
                    $('.' + provider + '-help').show();
                } else {
                    $modelRow.hide();
                    $testButton.prop('disabled', true);
                    $providerHelp.hide();
                }
            });
            
            // Function to update button states
            function updateButtonStates() {
                var provider = $('input[name="aisb_provider"]:checked').val();
                var apiKey = $('#aisb_api_key').val();
                var hasSavedKey = $('#aisb_api_key').data('has-saved-key') === 'true';
                var model = $('#aisb_model').val();
                
                // Enable test button if we have provider and either new or saved key
                var canTest = provider && (apiKey || hasSavedKey);
                $('#aisb-test-connection').prop('disabled', !canTest);
                
                // Enable save button only after successful verification
                $('#submit').prop('disabled', !connectionVerified);
                
                // Update save button text based on state
                if (connectionVerified) {
                    $('#submit').removeClass('button-secondary').addClass('button-primary');
                } else {
                    $('#submit').removeClass('button-primary').addClass('button-secondary');
                }
            }
            
            // Initial button state
            updateButtonStates();
            
            // Update button states on input changes
            $('input[name="aisb_provider"]').on('change', function() {
                connectionVerified = false;
                $('#aisb_keep_existing_key').val('0'); // Reset if provider changes
                updateButtonStates();
            });
            
            $('#aisb_api_key').on('input', function() {
                connectionVerified = false;
                $('#aisb_keep_existing_key').val('0'); // New key entered
                updateButtonStates();
            });
            
            $('#aisb_model').on('change', function() {
                connectionVerified = false;
                updateButtonStates();
            });
            
            // Test connection
            $('#aisb-test-connection').on('click', function() {
                var $button = $(this);
                var $spinner = $('.spinner');
                var $message = $('#aisb-settings-message');
                
                var provider = $('input[name="aisb_provider"]:checked').val();
                var apiKey = $('#aisb_api_key').val();
                var hasSavedKey = $('#aisb_api_key').data('has-saved-key') === 'true';
                var model = $('#aisb_model').val();
                
                if (!provider) {
                    alert('<?php _e('Please select an AI provider.', 'ai-section-builder'); ?>');
                    return;
                }
                
                if (!apiKey && !hasSavedKey) {
                    alert('<?php _e('Please enter an API key.', 'ai-section-builder'); ?>');
                    return;
                }
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $message.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aisb_test_ai_connection',
                        provider: provider,
                        api_key: apiKey,
                        model: model,
                        nonce: $('#aisb_ai_settings_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('notice-error').addClass('notice-success notice')
                                .html('<p><strong><?php _e('Success!', 'ai-section-builder'); ?></strong> ' + response.data.message + '</p>')
                                .show();
                            
                            $('#aisb-connection-status').html(
                                '<span style="color: #00a32a;">' +
                                '<span class="dashicons dashicons-yes-alt"></span> ' +
                                '<?php _e('Connected', 'ai-section-builder'); ?>' +
                                '</span>'
                            );
                            
                            // Mark connection as verified and enable save button
                            connectionVerified = true;
                            updateButtonStates();
                        } else {
                            $message.removeClass('notice-success').addClass('notice-error notice')
                                .html('<p><strong><?php _e('Error:', 'ai-section-builder'); ?></strong> ' + response.data + '</p>')
                                .show();
                            
                            // Mark connection as not verified
                            connectionVerified = false;
                            updateButtonStates();
                        }
                    },
                    error: function() {
                        $message.removeClass('notice-success').addClass('notice-error')
                            .html('<p><strong><?php _e('Error:', 'ai-section-builder'); ?></strong> <?php _e('Connection test failed. Please check your settings.', 'ai-section-builder'); ?></p>')
                            .show();
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });
            
            // Save settings
            $('#aisb-ai-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $spinner = $('.spinner');
                var $message = $('#aisb-settings-message');
                var $submitButton = $('#submit');
                
                $submitButton.prop('disabled', true);
                $spinner.addClass('is-active');
                $message.hide();
                
                // Determine if we should keep existing key
                var apiKeyField = $('#aisb_api_key');
                var apiKeyValue = apiKeyField.val();
                var hasSavedKey = apiKeyField.data('has-saved-key') === 'true' || apiKeyField.data('has-saved-key') === true;
                var keepExisting = (apiKeyValue === '' && hasSavedKey) ? '1' : '0';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aisb_save_ai_settings',
                        provider: $('input[name="aisb_provider"]:checked').val(),
                        api_key: apiKeyValue,
                        keep_existing_key: keepExisting,
                        model: $('#aisb_model').val(),
                        nonce: $('#aisb_ai_settings_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('notice-error').addClass('notice-success notice')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                            
                            // Update connection status if verified
                            if (response.data.verified) {
                                $('#aisb-connection-status').html(
                                    '<span style="color: #00a32a;">' +
                                    '<span class="dashicons dashicons-yes-alt"></span> ' +
                                    '<?php _e('Connected', 'ai-section-builder'); ?>' +
                                    '</span>'
                                );
                            }
                            
                            // Update the field state after successful save
                            if (apiKeyValue) {
                                // New key was saved
                                $('#aisb_api_key').val('').data('has-saved-key', 'true');
                                $('#aisb_keep_existing_key').val('1');
                            }
                            
                            // Clear API key field and update placeholder
                            if (response.data.masked_key) {
                                $('#aisb_api_key').attr('placeholder', response.data.masked_key);
                            }
                        } else {
                            $message.removeClass('notice-success').addClass('notice-error notice')
                                .html('<p><strong><?php _e('Error:', 'ai-section-builder'); ?></strong> ' + response.data + '</p>')
                                .show();
                        }
                    },
                    error: function() {
                        $message.removeClass('notice-success').addClass('notice-error notice')
                            .html('<p><strong><?php _e('Error:', 'ai-section-builder'); ?></strong> <?php _e('Failed to save settings.', 'ai-section-builder'); ?></p>')
                            .show();
                    },
                    complete: function() {
                        $submitButton.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
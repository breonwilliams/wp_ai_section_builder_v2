<?php
/**
 * Plugin Name: AI Section Builder Pro
 * Plugin URI: https://github.com/breonwilliams/ai-section-builder
 * Description: AI-powered section builder for WordPress. Build beautiful pages with pre-designed sections or generate content from documents using AI.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Breon Williams
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-section-builder
 * Network: false
 * 
 * @package AISectiionBuilder
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AISB_VERSION', '2.0.0');
define('AISB_PLUGIN_FILE', __FILE__);
define('AISB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AISB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AISB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * PHASE 1: Basic Plugin Foundation
 * 
 * This is the absolute minimum to:
 * 1. Activate the plugin
 * 2. Add ONE section type (Hero)
 * 3. Simple admin interface
 * 4. Save/render functionality
 * 
 * DO NOT ADD MORE FEATURES UNTIL USER TESTS THIS WORKS
 */

// Initialize plugin
add_action('plugins_loaded', 'aisb_init');

function aisb_init() {
    // Load text domain for translations
    load_plugin_textdomain('ai-section-builder', false, dirname(AISB_PLUGIN_BASENAME) . '/languages');
    
    // Run migration to clean up old Phase 1A data
    aisb_migrate_cleanup_old_data();
    
    // Initialize plugin
    aisb_setup();
}

function aisb_setup() {
    // Load Color Settings class
    require_once AISB_PLUGIN_DIR . 'includes/class-aisb-color-settings.php';
    
    // Hook into WordPress
    add_action('init', 'aisb_register_post_type');
    add_action('admin_menu', 'aisb_add_admin_menu');
    add_action('add_meta_boxes', 'aisb_add_meta_boxes');
    add_action('save_post', 'aisb_save_meta_box');
    
    // Page builder functionality - override templates (late priority to avoid conflicts)
    add_filter('template_include', 'aisb_template_override', 999);
    add_filter('body_class', 'aisb_body_class');
    
    // Admin notices for conflicts
    add_action('admin_notices', 'aisb_admin_notices');
    
    // Visual editor page
    add_action('admin_menu', 'aisb_add_editor_page');
    add_action('admin_init', 'aisb_handle_editor_redirect');
    
    // REST API endpoint for page/post search
    add_action('rest_api_init', 'aisb_register_rest_routes');
    
    // Enqueue styles with high priority (99) to ensure they load after theme styles
    add_action('wp_enqueue_scripts', 'aisb_enqueue_styles', 99);
    add_action('admin_enqueue_scripts', 'aisb_enqueue_admin_styles');
    
    // Performance optimizations
    add_action('save_post', 'aisb_clear_cache_on_save', 20);
    add_action('delete_post', 'aisb_clear_cache_on_delete');
    
    // AJAX handlers
    add_action('wp_ajax_aisb_activate_builder', 'aisb_ajax_activate_builder');
    add_action('wp_ajax_aisb_deactivate_builder', 'aisb_ajax_deactivate_builder');
    add_action('wp_ajax_aisb_save_sections', 'aisb_ajax_save_sections');
    add_action('wp_ajax_aisb_render_form', 'aisb_ajax_render_form');
}

/**
 * Register custom post type for sections library (future use)
 */
function aisb_register_post_type() {
    // We'll add this later when needed
    // For now, we'll just use post meta
}

/**
 * Add admin menu
 */
function aisb_add_admin_menu() {
    add_menu_page(
        __('AI Section Builder', 'ai-section-builder'),
        __('AI Section Builder', 'ai-section-builder'),
        'manage_options',
        'ai-section-builder',
        'aisb_admin_page',
        'dashicons-layout',
        30
    );
    
    // Add editor submenu - points to the real working editor
    add_submenu_page(
        'ai-section-builder',
        __('Section Editor', 'ai-section-builder'),
        __('Section Editor', 'ai-section-builder'),
        'edit_posts',
        'ai-section-builder-editor',
        'aisb_render_editor_page'
    );
    
    // Add AI Settings submenu
    add_submenu_page(
        'ai-section-builder',
        __('AI Settings', 'ai-section-builder'),
        __('AI Settings', 'ai-section-builder'),
        'manage_options',
        'ai-section-builder-settings',
        'aisb_render_ai_settings_page'
    );
}

/**
 * Main admin dashboard page
 */
function aisb_admin_page() {
    ?>
    <div class="aisb-admin-wrap">
        <div class="aisb-admin-header">
            <h1 class="aisb-admin-header__title"><?php _e('AI Section Builder Pro', 'ai-section-builder'); ?></h1>
            <p class="aisb-admin-header__subtitle"><?php _e('Create Beautiful Page Sections with Visual Editor', 'ai-section-builder'); ?></p>
        </div>
        
        <!-- Welcome Section -->
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-admin-home"></span>
                </div>
                <h2 class="aisb-section-card__title">Welcome to AI Section Builder</h2>
            </div>
            <div class="aisb-section-card__content">
                <p>Create stunning page sections with our intuitive visual editor. Build professional layouts without writing code.</p>
                
                <h3>Available Section Types:</h3>
                <ul>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Hero Sections</strong> - Eye-catching headers with multiple layouts</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Hero with Form</strong> - Hero section with integrated form area</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Features Grid</strong> - Showcase your services or products</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Testimonials</strong> - Display customer reviews and feedback</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>FAQ Sections</strong> - Answer common questions</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Statistics</strong> - Show impressive numbers and metrics</li>
                    <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> <strong>Checklists</strong> - Present information in organized lists</li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <p><strong>Ready to get started?</strong> Edit any page or post and click "Activate AI Section Builder" to begin creating beautiful sections.</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <h2 class="aisb-section-card__title">Quick Actions</h2>
            </div>
            <div class="aisb-section-card__content">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="<?php echo admin_url('admin.php?page=ai-section-builder-editor'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-edit" style="margin-right: 5px; margin-top: 3px;"></span>
                        Open Section Editor
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button">
                        <span class="dashicons dashicons-admin-page" style="margin-right: 5px; margin-top: 3px;"></span>
                        View Pages
                    </a>
                    <a href="<?php echo admin_url('edit.php'); ?>" class="button">
                        <span class="dashicons dashicons-admin-post" style="margin-right: 5px; margin-top: 3px;"></span>
                        View Posts
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Usage Statistics -->
        <div class="aisb-section-card">
            <div class="aisb-section-card__header">
                <div class="aisb-section-card__icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <h2 class="aisb-section-card__title">Debug Information</h2>
            </div>
            <div class="aisb-section-card__content">
                <?php
            global $wpdb;
            $posts_with_sections = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_aisb_sections'");
            $posts_enabled = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_aisb_enabled' AND meta_value = '1'");
                ?>
                <ul>
                <li>Posts with sections data: <?php echo intval($posts_with_sections); ?></li>
                <li>Posts with AISB enabled: <?php echo intval($posts_enabled); ?></li>
                <li>WordPress Version: <?php echo get_bloginfo('version'); ?></li>
                <li>PHP Version: <?php echo phpversion(); ?></li>
                <li>Active Theme: <?php echo wp_get_theme()->get('Name'); ?></li>
                </ul>
                
                <p><strong>To debug a specific page:</strong> Add <code>?aisb_debug=1</code> to any page URL on the frontend.</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render AI Settings page
 */
function aisb_render_ai_settings_page() {
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
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_save_ai_settings',
                    provider: $('input[name="aisb_provider"]:checked').val(),
                    api_key: $('#aisb_api_key').val(),
                    keep_existing_key: $('#aisb_keep_existing_key').val(),
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
                        
                        // Clear API key field and update placeholder
                        if (response.data.masked_key) {
                            $('#aisb_api_key').val('').attr('placeholder', response.data.masked_key);
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

/**
 * Placeholder editor page - Phase 1B
 * DEPRECATED: This function is no longer used. The submenu now points to aisb_render_editor_page()
 * Keeping this commented out for reference only
 */
/*
function aisb_editor_page() {
    // Get post ID if provided
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    $post = $post_id ? get_post($post_id) : null;
    
    ?>
    <div class="wrap">
        <h1><?php _e('AI Section Builder - Visual Editor', 'ai-section-builder'); ?></h1>
        
        <?php if ($post): ?>
            <div style="background: #e7f3ff; border: 1px solid #0073aa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;">🚧 <?php _e('Editor Coming Soon', 'ai-section-builder'); ?></h2>
                <p><strong><?php _e('Editing:', 'ai-section-builder'); ?></strong> <?php echo esc_html($post->post_title); ?></p>
                <p><?php _e('The visual editor interface is currently under development. This is where you will:', 'ai-section-builder'); ?></p>
                
                <ul style="margin-left: 20px;">
                    <li><?php _e('Drag and drop sections to build your page', 'ai-section-builder'); ?></li>
                    <li><?php _e('Customize section content and styling', 'ai-section-builder'); ?></li>
                    <li><?php _e('Use AI to generate content from documents', 'ai-section-builder'); ?></li>
                    <li><?php _e('Preview changes in real-time', 'ai-section-builder'); ?></li>
                </ul>
                
                <h3><?php _e('Current Development Phase', 'ai-section-builder'); ?></h3>
                <div style="background: #fff; padding: 15px; border-radius: 4px; border-left: 3px solid #00a32a;">
                    <p><strong><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Phase 1B Complete:</strong></p>
                    <ul style="margin-left: 20px;">
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Smart page builder conflict detection</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Safe template override system</li>
                        <li><span class="dashicons dashicons-yes" style="color: #00a32a;"></span> Professional activation workflow</li>
                    </ul>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 3px solid #dba617; margin-top: 10px;">
                    <p><strong>🚧 Next Phase:</strong></p>
                    <ul style="margin-left: 20px;">
                        <li>🔨 Professional file structure</li>
                        <li>🔨 Dark theme editor interface design</li>
                        <li>🔨 Hero section variants (light/dark, 3 layouts)</li>
                        <li>🔨 Frontend-first development approach</li>
                    </ul>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-primary">
                        <span class="dashicons dashicons-arrow-left-alt" style="margin-right: 5px;"></span>
                        <?php _e('Back to Post Editor', 'ai-section-builder'); ?>
                    </a>
                    
                    <?php if (aisb_is_enabled($post_id)): ?>
                        <a href="<?php echo get_permalink($post_id); ?>" class="button" target="_blank" style="margin-left: 10px;">
                            <span class="dashicons dashicons-visibility" style="margin-right: 5px;"></span>
                            <?php _e('View Page', 'ai-section-builder'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <div style="background: #fff3cd; border: 1px solid #dba617; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><span class="dashicons dashicons-warning" style="color: #dba617;"></span> <?php _e('No Post Selected', 'ai-section-builder'); ?></h2>
                <p><?php _e('To use the AI Section Builder editor, please:', 'ai-section-builder'); ?></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Go to any page or post in WordPress', 'ai-section-builder'); ?></li>
                    <li><?php _e('Look for the "AI Section Builder" meta box', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Build with AI Section Builder" to activate', 'ai-section-builder'); ?></li>
                    <li><?php _e('Then click "Edit with AI Section Builder" to open this editor', 'ai-section-builder'); ?></li>
                </ol>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-page" style="margin-right: 5px;"></span>
                        <?php _e('Go to Pages', 'ai-section-builder'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php'); ?>" class="button" style="margin-left: 10px;">
                        <span class="dashicons dashicons-admin-post" style="margin-right: 5px;"></span>
                        <?php _e('Go to Posts', 'ai-section-builder'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Development Progress -->
        <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3><?php _e('Development Progress', 'ai-section-builder'); ?></h3>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #00a32a;"><span class="dashicons dashicons-yes"></span> Completed Features</h4>
                    <ul>
                        <li>Plugin foundation & activation</li>
                        <li>Page builder conflict detection</li>
                        <li>Template override system</li>
                        <li>Content backup & restoration</li>
                        <li>Professional activation workflow</li>
                    </ul>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #dba617;">🚧 In Development</h4>
                    <ul>
                        <li>Visual editor interface</li>
                        <li>Hero section variants</li>
                        <li>Frontend design system</li>
                        <li>Section management</li>
                    </ul>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="color: #666;">📋 Planned Features</h4>
                    <ul>
                        <li>13 section types</li>
                        <li>AI document processing</li>
                        <li>Template library</li>
                        <li>Global settings</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}
*/

/**
 * Add hidden editor page for visual builder
 */
function aisb_add_editor_page() {
    add_submenu_page(
        null, // No parent menu - hidden page
        __('AI Section Builder Editor', 'ai-section-builder'),
        __('Editor', 'ai-section-builder'),
        'edit_posts',
        'aisb-editor',
        'aisb_render_editor_page'
    );
}

/**
 * Handle redirect to editor when clicking "Build with AI Section Builder"
 */
function aisb_handle_editor_redirect() {
    if (isset($_GET['aisb_edit']) && isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        
        // Verify user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('You do not have permission to edit this post.', 'ai-section-builder'));
        }
        
        // Redirect to editor page with post ID
        wp_redirect(admin_url('admin.php?page=aisb-editor&post_id=' . $post_id));
        exit;
    }
}

/**
 * Render the visual editor page
 */
function aisb_render_editor_page() {
    // Get post ID from URL
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!$post_id) {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Section Builder - Visual Editor', 'ai-section-builder'); ?></h1>
            <div style="background: #fff3cd; border: 1px solid #dba617; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><span class="dashicons dashicons-warning" style="color: #dba617;"></span> <?php _e('No Post Selected', 'ai-section-builder'); ?></h2>
                <p><?php _e('The Section Editor requires a specific page or post to edit. To use the visual editor:', 'ai-section-builder'); ?></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Go to any page or post in WordPress', 'ai-section-builder'); ?></li>
                    <li><?php _e('Look for the "AI Section Builder" meta box in the editor sidebar', 'ai-section-builder'); ?></li>
                    <li><?php _e('Click "Build with AI Section Builder" to activate the plugin for that page', 'ai-section-builder'); ?></li>
                    <li><?php _e('Then click "Edit with AI Section Builder" to open the visual editor', 'ai-section-builder'); ?></li>
                </ol>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-page" style="margin-right: 5px;"></span>
                        <?php _e('Go to Pages', 'ai-section-builder'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php'); ?>" class="button" style="margin-left: 10px;">
                        <span class="dashicons dashicons-admin-post" style="margin-right: 5px;"></span>
                        <?php _e('Go to Posts', 'ai-section-builder'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return;
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_die(__('Post not found.', 'ai-section-builder'));
    }
    
    // Get existing sections
    $sections = get_post_meta($post_id, '_aisb_sections', true);
    if (!is_array($sections)) {
        $sections = [];
    }
    ?>
    <div class="aisb-editor-wrapper">
        <!-- Editor Toolbar -->
        <div class="aisb-editor-toolbar">
            <div class="aisb-editor-toolbar__left">
                <a href="<?php echo get_edit_post_link($post_id); ?>" class="aisb-editor-btn aisb-editor-btn-ghost">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Editor', 'ai-section-builder'); ?>
                </a>
            </div>
            <div class="aisb-editor-toolbar__center">
                <h1 class="aisb-editor-title">
                    <?php echo esc_html($post->post_title); ?>
                </h1>
            </div>
            <div class="aisb-editor-toolbar__right">
                <div class="aisb-toolbar-group">
                    <button class="aisb-editor-btn aisb-editor-btn-ghost aisb-sidebar-toggle active" 
                            id="aisb-toggle-sidebars" 
                            title="<?php _e('Toggle Sidebars (Shift+S)', 'ai-section-builder'); ?>"
                            aria-label="<?php _e('Toggle sidebars visibility', 'ai-section-builder'); ?>"
                            aria-pressed="true">
                        <span class="dashicons dashicons-editor-contract"></span>
                        <span class="aisb-btn-label"><?php _e('Hide Panels', 'ai-section-builder'); ?></span>
                    </button>
                </div>
                <button class="aisb-editor-btn aisb-editor-btn-primary" id="aisb-save-sections">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save', 'ai-section-builder'); ?>
                </button>
            </div>
        </div>
        
        <!-- Main Editor Layout -->
        <div class="aisb-editor-layout">
            <!-- Left Panel - Tabbed Interface -->
            <div class="aisb-editor-panel aisb-editor-panel--left" id="aisb-left-panel">
                <!-- Tab Navigation -->
                <div class="aisb-panel-tabs">
                    <button class="aisb-panel-tab active" data-panel="sections" id="aisb-tab-sections">
                        <span class="dashicons dashicons-layout"></span>
                        <span class="aisb-tab-label"><?php _e('Sections', 'ai-section-builder'); ?></span>
                    </button>
                    <button class="aisb-panel-tab" data-panel="settings" id="aisb-tab-settings">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span class="aisb-tab-label"><?php _e('Settings', 'ai-section-builder'); ?></span>
                    </button>
                </div>
                
                <!-- Sections Panel (Library + Edit modes) -->
                <div id="aisb-panel-sections" class="aisb-panel-content active">
                    <!-- Library Mode -->
                    <div id="aisb-library-mode" class="aisb-panel-mode">
                        <div class="aisb-editor-panel__header">
                            <h2><?php _e('Add Section', 'ai-section-builder'); ?></h2>
                        </div>
                        <div class="aisb-editor-panel__content">
                            <button class="aisb-section-type" data-type="hero">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-megaphone"></span>
                                </div>
                                <div class="aisb-section-type__label">Hero Section</div>
                                <div class="aisb-section-type__description">Eye-catching opener with headline and CTA</div>
                            </button>
                            <button class="aisb-section-type" data-type="hero-form">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-feedback"></span>
                                </div>
                                <div class="aisb-section-type__label">Hero with Form</div>
                                <div class="aisb-section-type__description">Hero section with form area</div>
                            </button>
                            <button class="aisb-section-type" data-type="features">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-screenoptions"></span>
                                </div>
                                <div class="aisb-section-type__label">Features Section</div>
                                <div class="aisb-section-type__description">Showcase key features with icons and descriptions</div>
                            </button>
                            <button class="aisb-section-type" data-type="checklist">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aisb-section-type__label">Checklist Section</div>
                                <div class="aisb-section-type__description">List benefits or features with checkmarks</div>
                            </button>
                            <button class="aisb-section-type" data-type="faq">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-editor-help"></span>
                                </div>
                                <div class="aisb-section-type__label">FAQ Section</div>
                                <div class="aisb-section-type__description">Answer frequently asked questions</div>
                            </button>
                            <button class="aisb-section-type" data-type="stats">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                </div>
                                <div class="aisb-section-type__label">Stats Section</div>
                                <div class="aisb-section-type__description">Display key metrics and numbers</div>
                            </button>
                            <button class="aisb-section-type" data-type="testimonials">
                                <div class="aisb-section-type__icon">
                                    <span class="dashicons dashicons-format-quote"></span>
                                </div>
                                <div class="aisb-section-type__label">Testimonials Section</div>
                                <div class="aisb-section-type__description">Showcase customer reviews and testimonials</div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="aisb-edit-mode" class="aisb-panel-mode" style="display: none;">
                        <div class="aisb-editor-panel__header">
                            <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-back-to-library">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                                <?php _e('Back to Library', 'ai-section-builder'); ?>
                            </button>
                        </div>
                        <div class="aisb-editor-panel__content" id="aisb-edit-content">
                            <!-- Section edit form will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Settings Panel -->
                <div id="aisb-panel-settings" class="aisb-panel-content" style="display: none;">
                    <div class="aisb-editor-panel__header">
                        <h2><?php _e('Global Settings', 'ai-section-builder'); ?></h2>
                    </div>
                    <div class="aisb-editor-panel__content aisb-settings-content">
                        <?php 
                        // Get saved colors
                        $color_settings = \AISB\Settings\Color_Settings::get_instance();
                        $primary_color = $color_settings->get_primary_color();
                        $base_light_color = $color_settings->get_base_light_color();
                        $base_dark_color = $color_settings->get_base_dark_color();
                        $text_light_color = $color_settings->get_text_light_color();
                        $text_dark_color = $color_settings->get_text_dark_color();
                        $secondary_light_color = $color_settings->get_secondary_light_color();
                        $secondary_dark_color = $color_settings->get_secondary_dark_color();
                        $border_light_color = $color_settings->get_border_light_color();
                        $border_dark_color = $color_settings->get_border_dark_color();
                        $muted_light_color = $color_settings->get_muted_light_color();
                        $muted_dark_color = $color_settings->get_muted_dark_color();
                        
                        ?>
                        
                        <!-- Light Mode Colors -->
                        <div class="aisb-settings-group">
                            <h4 class="aisb-settings-group__title"><?php _e('Light Mode Colors', 'ai-section-builder'); ?></h4>
                            
                            <!-- Primary Color -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-primary"><?php _e('Primary Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-primary" name="colors[primary]" value="<?php echo esc_attr($primary_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($primary_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Used for buttons, links, and interactive elements', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Background Color (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-base-light"><?php _e('Background Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-base-light" name="colors[base_light]" value="<?php echo esc_attr($base_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($base_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main background color for light sections', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Text Color (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-text-light"><?php _e('Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-text-light" name="colors[text_light]" value="<?php echo esc_attr($text_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($text_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main text color for light sections', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Muted Text Color (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-muted-light"><?php _e('Muted Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-muted-light" name="colors[muted_light]" value="<?php echo esc_attr($muted_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($muted_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Secondary text color for descriptions and subtle content', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Secondary Background (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-secondary-light"><?php _e('Secondary Background', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-secondary-light" name="colors[secondary_light]" value="<?php echo esc_attr($secondary_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($secondary_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Background color for cards and alternate sections', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Border Color (Light) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-border-light"><?php _e('Border Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-border-light" name="colors[border_light]" value="<?php echo esc_attr($border_light_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($border_light_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Border color for cards and dividers', 'ai-section-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Dark Mode Colors -->
                        <div class="aisb-settings-group">
                            <h4 class="aisb-settings-group__title"><?php _e('Dark Mode Colors', 'ai-section-builder'); ?></h4>
                            
                            <!-- Background Color (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-base-dark"><?php _e('Background Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-base-dark" name="colors[base_dark]" value="<?php echo esc_attr($base_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($base_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main background color for dark sections', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Text Color (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-text-dark"><?php _e('Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-text-dark" name="colors[text_dark]" value="<?php echo esc_attr($text_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($text_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Main text color for dark sections', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Muted Text Color (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-muted-dark"><?php _e('Muted Text Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-muted-dark" name="colors[muted_dark]" value="<?php echo esc_attr($muted_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($muted_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Secondary text color for descriptions in dark mode', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Secondary Background (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-secondary-dark"><?php _e('Secondary Background', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-secondary-dark" name="colors[secondary_dark]" value="<?php echo esc_attr($secondary_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($secondary_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Background color for cards in dark mode', 'ai-section-builder'); ?></p>
                            </div>
                            
                            <!-- Border Color (Dark) -->
                            <div class="aisb-settings-field">
                                <label for="aisb-gs-border-dark"><?php _e('Border Color', 'ai-section-builder'); ?></label>
                                <div class="aisb-color-input-wrapper">
                                    <input type="color" id="aisb-gs-border-dark" name="colors[border_dark]" value="<?php echo esc_attr($border_dark_color); ?>" />
                                    <input type="text" class="aisb-color-text" value="<?php echo esc_attr($border_dark_color); ?>" />
                                </div>
                                <p class="aisb-settings-help"><?php _e('Border color for cards in dark mode', 'ai-section-builder'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Settings Actions -->
                        <div class="aisb-settings-actions" style="display: flex; flex-direction: column; gap: 12px;">
                            <button class="aisb-editor-btn aisb-editor-btn-ghost" id="aisb-reset-global-settings">
                                <span class="dashicons dashicons-image-rotate"></span>
                                <?php _e('Reset to Default', 'ai-section-builder'); ?>
                            </button>
                            <p class="aisb-settings-help" style="text-align: center; margin: 0; font-size: 13px; color: #9CA3AF;">
                                <?php _e('Use the main Save button above to save all changes', 'ai-section-builder'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Center - Canvas/Preview -->
            <div class="aisb-editor-canvas">
                <div class="aisb-editor-canvas__inner">
                    <div class="aisb-editor-sections" id="aisb-sections-preview">
                        <?php if (empty($sections)): ?>
                            <div class="aisb-editor-empty-state">
                                <span class="dashicons dashicons-layout"></span>
                                <h2><?php _e('Start Building Your Page', 'ai-section-builder'); ?></h2>
                                <p><?php _e('Click a section type to add it to your page', 'ai-section-builder'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel - Page Structure -->
            <div class="aisb-editor-panel aisb-editor-panel--right" id="aisb-structure-panel">
                <div class="aisb-editor-panel__header">
                    <h2><?php _e('Page Structure', 'ai-section-builder'); ?></h2>
                </div>
                <div class="aisb-editor-panel__content" id="aisb-structure-content">
                    <!-- Empty state outside of sortable container -->
                    <div class="aisb-structure-empty" style="display: none;">
                        <span class="dashicons dashicons-editor-ul" aria-hidden="true"></span>
                        <p><?php _e('No sections added yet', 'ai-section-builder'); ?></p>
                    </div>
                    <!-- Sortable container for sections only -->
                    <div id="aisb-section-list" role="list" aria-label="Page sections">
                        <!-- Section items will be rendered here by JavaScript -->
                    </div>
                    
                    <!-- Screen reader instructions -->
                    <div id="aisb-reorder-instructions" class="screen-reader-text">
                        <?php _e('Use arrow keys to navigate sections. Press Enter to select, then use arrow keys to move sections up or down. Press Enter again to confirm position.', 'ai-section-builder'); ?>
                    </div>
                </div>
            </div>
        </div>
        
                </div>
            </div>
        </div>
        
        <!-- Hidden data -->
        <input type="hidden" id="aisb-post-id" value="<?php echo $post_id; ?>" />
        <input type="hidden" id="aisb-existing-sections" value="<?php echo esc_attr(json_encode($sections)); ?>" />
        <?php wp_nonce_field('aisb_editor_nonce', 'aisb_editor_nonce'); ?>
    </div>
    <?php
}

/**
 * Add meta boxes to posts and pages
 */
function aisb_add_meta_boxes() {
    $post_types = ['post', 'page'];
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'aisb_sections',
            __('AI Section Builder', 'ai-section-builder'),
            'aisb_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}

/**
 * Meta box content - Conflict-aware page builder activation
 */
function aisb_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('aisb_meta_box_action', 'aisb_nonce');
    
    // Detect active page builders
    $active_builders = aisb_detect_active_builders($post->ID);
    $conflict_check = aisb_check_conflicts($post->ID);
    $is_aisb_enabled = aisb_is_enabled($post->ID);
    
    ?>
    <div class="aisb-meta-box">
        <div class="aisb-meta-box__inner">
            
            <!-- Builder Detection Status -->
            <?php if (empty($active_builders)): ?>
                <div class="aisb-status-card aisb-status-ready">
                    <div class="aisb-status-card__header">
                        <span class="aisb-status-card__icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </span>
                        <span>Ready for Page Builder</span>
                    </div>
                    <div class="aisb-status-card__content">
                        <p>No page builders detected. You can activate AI Section Builder.</p>
                    </div>
                </div>
            <?php elseif ($is_aisb_enabled && !$conflict_check['has_conflicts']): ?>
                <div class="aisb-status-card aisb-status-active">
                    <div class="aisb-status-card__header">
                        <span class="aisb-status-card__icon">
                            <span class="dashicons dashicons-admin-customizer"></span>
                        </span>
                        <span>AI Section Builder Active</span>
                    </div>
                    <div class="aisb-status-card__content">
                        <p>This page is using AI Section Builder for layout.</p>
                    </div>
                </div>
            <?php elseif ($conflict_check['has_conflicts']): ?>
                <div class="aisb-status-card aisb-status-error">
                    <div class="aisb-status-card__header">
                        <span class="aisb-status-card__icon">
                            <span class="dashicons dashicons-warning"></span>
                        </span>
                        <span>Page Builder Conflict Detected</span>
                    </div>
                    <div class="aisb-status-card__content">
                        <p>Active builders on this page:</p>
                        <ul class="aisb-status-card__list">
                            <?php foreach ($active_builders as $builder): ?>
                                <li><?php echo esc_html(aisb_get_builder_name($builder)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="aisb-status-card aisb-status-neutral">
                    <div class="aisb-status-card__header">
                        <span class="aisb-status-card__icon">
                            <span class="dashicons dashicons-media-document"></span>
                        </span>
                        <span>Other Page Builder Active</span>
                    </div>
                    <div class="aisb-status-card__content">
                        <p>This page is using:</p>
                        <ul class="aisb-status-card__list">
                            <?php foreach ($active_builders as $builder): ?>
                                <li><strong><?php echo esc_html(aisb_get_builder_name($builder)); ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="aisb-meta-box__actions">
                <?php if (empty($active_builders)): ?>
                    <!-- No builders - direct activation -->
                    <button type="button" class="aisb-btn aisb-btn-primary aisb-activate-builder" 
                            data-action="activate" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-layout"></span>
                        <?php _e('Build with AI Section Builder', 'ai-section-builder'); ?>
                    </button>
                    <p class="aisb-meta-box__description">
                        Create a custom page layout using AI-powered sections and design tools.
                    </p>
                
                <?php elseif ($is_aisb_enabled && !$conflict_check['has_conflicts']): ?>
                    <!-- AISB active - edit button -->
                    <button type="button" class="aisb-btn aisb-btn-primary aisb-edit-builder" 
                            data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Edit with AI Section Builder', 'ai-section-builder'); ?>
                    </button>
                    
                    <button type="button" class="aisb-btn aisb-btn-secondary aisb-deactivate-builder" 
                            data-action="deactivate" data-post-id="<?php echo $post->ID; ?>">
                        <?php _e('Deactivate Builder', 'ai-section-builder'); ?>
                    </button>
                
            <?php elseif ($conflict_check['has_conflicts']): ?>
                <!-- Conflicts - show warning and switch option -->
                <button type="button" class="aisb-btn aisb-btn-danger aisb-switch-builder" 
                        data-action="switch" data-post-id="<?php echo $post->ID; ?>"
                        onclick="return aisb_confirm_switch();">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Switch to AI Section Builder', 'ai-section-builder'); ?>
                </button>
                <p class="aisb-meta-box__description aisb-text-danger">
                    <strong>Warning:</strong> Switching builders will preserve your original content but deactivate other page builders on this page.
                </p>
                
            <?php else: ?>
                <!-- Other builder active - switch option -->
                <button type="button" class="aisb-btn aisb-btn-secondary aisb-switch-builder" 
                        data-action="switch" data-post-id="<?php echo $post->ID; ?>"
                        onclick="return aisb_confirm_switch();">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Switch to AI Section Builder', 'ai-section-builder'); ?>
                </button>
                <p class="aisb-meta-box__description">
                    Switch from <?php echo esc_html(aisb_get_builder_name($active_builders[0])); ?> to AI Section Builder. Your original content will be preserved.
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Hidden fields for AJAX -->
        <input type="hidden" id="aisb-action" name="aisb_action" value="" />
        <input type="hidden" id="aisb-enabled" name="aisb_enabled" value="<?php echo $is_aisb_enabled ? '1' : '0'; ?>" />
    </div>
    
    <script>
        function aisb_confirm_switch() {
            return confirm('Are you sure you want to switch to AI Section Builder? This will deactivate other page builders on this page, but your original content will be preserved.');
        }
        
        jQuery(document).ready(function($) {
            $('.aisb-activate-builder, .aisb-switch-builder, .aisb-deactivate-builder').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $button = $(this);
                
                // Prevent double-clicking
                if ($button.prop('disabled')) {
                    return false;
                }
                
                var action = $button.data('action');
                var postId = $button.data('post-id');
                
                console.log('AISB: Button clicked', {action: action, postId: postId});
                
                // If activate or switch, redirect to editor
                if (action === 'activate' || action === 'switch') {
                    // Store original button text
                    var originalText = $button.text();
                    
                    // Show loading state immediately
                    $button.prop('disabled', true).text('Loading editor...');
                    
                    // First save the activation state via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aisb_activate_builder',
                            post_id: postId,
                            builder_action: action,
                            nonce: $('[name="aisb_nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                // Small delay to ensure database write completes, then redirect to editor
                                setTimeout(function() {
                                    var adminUrl = <?php echo json_encode(admin_url('admin.php')); ?>;
                                    var editorUrl = adminUrl + '?page=aisb-editor&post_id=' + postId;
                                    console.log('AISB: Redirecting to:', editorUrl);
                                    window.location.href = editorUrl;
                                }, 100);
                            } else {
                                alert('Error activating builder: ' + response.data);
                                $button.prop('disabled', false).text(originalText);
                            }
                        },
                        error: function() {
                            alert('Error activating builder. Please try again.');
                            $button.prop('disabled', false).text(originalText);
                        }
                    });
                    
                    return;
                }
                
                // For deactivate, use AJAX like activate
                if (action === 'deactivate') {
                    // Store original button text
                    var originalText = $button.text();
                    
                    // Confirm deactivation
                    if (!confirm('Are you sure you want to deactivate AI Section Builder? Your sections will be preserved and can be restored if you reactivate.')) {
                        return false;
                    }
                    
                    // Show loading state
                    $button.prop('disabled', true).text('Deactivating...');
                    
                    // Make AJAX request to deactivate
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aisb_deactivate_builder',
                            post_id: postId,
                            nonce: $('[name="aisb_nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                console.log('AISB: Builder deactivated successfully');
                                
                                // Update button text temporarily to show success
                                $button.text('Deactivated!');
                                
                                // Reload the page after a short delay to update the meta box
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                alert('Error deactivating builder: ' + response.data);
                                $button.prop('disabled', false).text(originalText);
                            }
                        },
                        error: function() {
                            alert('Error deactivating builder. Please try again.');
                            $button.prop('disabled', false).text(originalText);
                        }
                    });
                    
                    return;
                }
            });
            
            $('.aisb-edit-builder').on('click', function() {
                var postId = $(this).data('post-id');
                var adminUrl = <?php echo json_encode(admin_url('admin.php')); ?>;
                var editorUrl = adminUrl + '?page=aisb-editor&post_id=' + postId;
                window.location.href = editorUrl;
            });
        });
    </script>
    <?php
}

/**
 * Save meta box data - Handle page builder activation/deactivation
 */
function aisb_save_meta_box($post_id) {
    // Add debug logging
    error_log("AISB: aisb_save_meta_box called for post $post_id");
    error_log("AISB: POST data: " . print_r($_POST, true));
    
    // Security checks
    if (!isset($_POST['aisb_nonce']) || !wp_verify_nonce($_POST['aisb_nonce'], 'aisb_meta_box_action')) {
        error_log("AISB: Nonce check failed");
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        error_log("AISB: Skipping autosave");
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        error_log("AISB: User capability check failed");
        return;
    }
    
    // Check if we have an action to process
    if (!isset($_POST['aisb_action']) || !isset($_POST['aisb_enabled'])) {
        error_log("AISB: No action or enabled field found");
        return;
    }
    
    $action = sanitize_text_field($_POST['aisb_action']);
    $enabled = sanitize_text_field($_POST['aisb_enabled']);
    
    error_log("AISB: Processing action: $action, enabled: $enabled");
    
    switch ($action) {
        case 'activate':
            // Direct activation - no other builders detected
            update_post_meta($post_id, '_aisb_enabled', 1);
            aisb_backup_original_content($post_id);
            
            // IMPORTANT: Clear any old sections data from Phase 1A
            // Phase 1B doesn't create sections - that's for Phase 2
            delete_post_meta($post_id, '_aisb_sections');
            
            // Clear cache and add success notice
            wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
            wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
            set_transient('aisb_activated_' . $post_id, true, 60);
            error_log("AISB: Activated for post $post_id - cleared old sections");
            break;
            
        case 'switch':
            // Switch from another builder - backup and switch
            aisb_backup_original_content($post_id);
            $switched_from = aisb_deactivate_other_builders($post_id);
            update_post_meta($post_id, '_aisb_enabled', 1);
            
            // IMPORTANT: Clear any old sections data from Phase 1A
            delete_post_meta($post_id, '_aisb_sections');
            
            // Clear cache and add success notice
            wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
            wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
            set_transient('aisb_switched_' . $post_id, $switched_from, 60);
            error_log("AISB: Switched for post $post_id from: " . implode(', ', $switched_from));
            break;
            
        case 'deactivate':
            // Deactivate AI Section Builder but PRESERVE sections
            error_log("AISB: Deactivating for post $post_id - preserving sections");
            
            update_post_meta($post_id, '_aisb_enabled', 0);
            
            // Important: We do NOT delete _aisb_sections anymore
            // This preserves the user's work for potential reactivation
            
            // Clear cache for this post
            wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
            
            // Add admin notice for successful deactivation
            set_transient('aisb_deactivated_' . $post_id, true, 60);
            
            error_log("AISB: Deactivated for post $post_id - sections preserved");
            break;
    }
}

/**
 * Backup original content before switching builders
 * 
 * @param int $post_id Post ID
 */
function aisb_backup_original_content($post_id) {
    $post = get_post($post_id);
    if ($post && !get_post_meta($post_id, '_aisb_original_content', true)) {
        update_post_meta($post_id, '_aisb_original_content', [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'date' => current_time('mysql')
        ]);
    }
}

/**
 * Deactivate other page builders on this post
 * 
 * @param int $post_id Post ID
 * @return array Array of builders that were deactivated
 */
function aisb_deactivate_other_builders($post_id) {
    // Store which builder we're switching from for reference
    $active_builders = aisb_detect_active_builders($post_id);
    $other_builders = array_diff($active_builders, ['aisb']);
    
    if (!empty($other_builders)) {
        update_post_meta($post_id, '_aisb_switched_from', $other_builders);
    }
    
    // Deactivate other builders by removing their meta keys
    // Note: We're not deleting the data, just the activation flags
    
    // Elementor
    if (get_post_meta($post_id, '_elementor_edit_mode', true)) {
        update_post_meta($post_id, '_elementor_edit_mode', '');
    }
    
    // Beaver Builder  
    if (get_post_meta($post_id, '_fl_builder_enabled', true)) {
        update_post_meta($post_id, '_fl_builder_enabled', 0);
    }
    
    // Divi
    if (get_post_meta($post_id, '_et_pb_use_builder', true) === 'on') {
        update_post_meta($post_id, '_et_pb_use_builder', 'off');
    }
    
    return $other_builders;
}

/**
 * AJAX handler for activating the builder
 */
function aisb_ajax_activate_builder() {
    // Check nonce - must match the action used in wp_nonce_field
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_meta_box_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $action = isset($_POST['builder_action']) ? sanitize_text_field($_POST['builder_action']) : '';
    
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Permission denied');
    }
    
    // Activate the builder
    update_post_meta($post_id, '_aisb_enabled', '1');
    
    // If switching from another builder, deactivate others
    if ($action === 'switch') {
        // Could add logic here to deactivate other builders if needed
    }
    
    // Initialize sections array if not exists
    $sections = get_post_meta($post_id, '_aisb_sections', true);
    if (!is_array($sections)) {
        update_post_meta($post_id, '_aisb_sections', []);
    }
    
    wp_send_json_success(['message' => 'Builder activated']);
}

/**
 * AJAX handler for deactivating the builder
 */
function aisb_ajax_deactivate_builder() {
    // Check nonce - must match the action used in wp_nonce_field
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_meta_box_action')) {
        wp_send_json_error('Security check failed');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Permission denied');
    }
    
    // Deactivate the builder but PRESERVE sections data
    update_post_meta($post_id, '_aisb_enabled', '0');
    
    // Important: We do NOT delete _aisb_sections here
    // This preserves the user's work for potential reactivation
    
    // Clear cache for this post
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
    
    // Log for debugging
    error_log("AISB: Deactivated via AJAX for post $post_id - sections preserved");
    
    wp_send_json_success([
        'message' => 'Builder deactivated. Your sections have been preserved and will be available if you reactivate.',
        'redirect' => false // No redirect needed, we'll reload the meta box
    ]);
}

/**
 * AJAX handler for saving sections
 */
function aisb_ajax_save_sections() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $sections = isset($_POST['sections']) ? $_POST['sections'] : '';
    
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Permission denied');
    }
    
    // Parse and validate sections
    $sections_array = json_decode(stripslashes($sections), true);
    if (!is_array($sections_array)) {
        $sections_array = [];
    }
    
    
    // Debug: Log what we're saving
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('AISB Saving Sections: ' . print_r($sections_array, true));
    }
    
    // Save sections
    update_post_meta($post_id, '_aisb_sections', $sections_array);
    
    // Clear cache for this post
    delete_transient('aisb_sections_' . $post_id);
    
    wp_send_json_success(['message' => 'Sections saved successfully']);
}

/**
 * AJAX handler for rendering form shortcodes
 */
function aisb_ajax_render_form() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_editor_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $form_type = isset($_POST['form_type']) ? sanitize_text_field($_POST['form_type']) : '';
    $form_shortcode = isset($_POST['form_shortcode']) ? stripslashes($_POST['form_shortcode']) : '';
    
    $html = '';
    
    if ($form_type === 'shortcode' && !empty($form_shortcode)) {
        // Process the shortcode - keep original format
        $html = do_shortcode($form_shortcode);
    }
    
    // If no form content, return placeholder with proper classes
    if (empty($html)) {
        ob_start();
        ?>
        <div class="aisb-form-placeholder">
            <form class="aisb-placeholder-form">
                <div class="aisb-form-field">
                    <input type="text" placeholder="Name" disabled class="aisb-form-input">
                </div>
                <div class="aisb-form-field">
                    <input type="email" placeholder="Email" disabled class="aisb-form-input">
                </div>
                <div class="aisb-form-field">
                    <input type="tel" placeholder="Phone" disabled class="aisb-form-input">
                </div>
                <div class="aisb-form-field">
                    <textarea placeholder="Message" disabled class="aisb-form-textarea" rows="4"></textarea>
                </div>
                <div class="aisb-form-field">
                    <button type="button" class="aisb-btn aisb-btn-primary" disabled>Submit</button>
                </div>
            </form>
        </div>
        <?php
        $html = ob_get_clean();
    }
    
    wp_send_json_success(['html' => $html, 'has_scripts' => strpos($html, '<script') !== false]);
}


/**
 * Override page template when AI Section Builder is active (conflict-aware)
 * Uses priority 999 to execute after other page builders
 */
function aisb_template_override($template) {
    // Only on singular posts/pages
    if (!is_singular(['post', 'page'])) {
        return $template;
    }
    
    $post_id = get_the_ID();
    
    error_log("AISB: Template override check for post $post_id");
    error_log("AISB: _aisb_enabled = " . get_post_meta($post_id, '_aisb_enabled', true));
    error_log("AISB: _aisb_sections = " . print_r(get_post_meta($post_id, '_aisb_sections', true), true));
    
    // Check for builder conflicts
    $conflict_check = aisb_check_conflicts($post_id);
    
    // If there are conflicts, don't override template
    if ($conflict_check['has_conflicts']) {
        error_log("AISB: Conflicts detected, not overriding template");
        // Set flag for admin notice
        set_transient('aisb_conflict_notice_' . $post_id, $conflict_check['conflicting_builders'], 300);
        return $template;
    }
    
    // Only override if AISB is the active builder
    if (!aisb_is_enabled($post_id)) {
        error_log("AISB: AISB not enabled, not overriding template");
        return $template;
    }
    
    error_log("AISB: Overriding template for post $post_id");
    // Load our custom template
    return aisb_get_canvas_template();
}

/**
 * Add body class for styling
 */
function aisb_body_class($classes) {
    if (is_singular(['post', 'page']) && aisb_has_sections()) {
        $classes[] = 'aisb-canvas';
        $classes[] = 'aisb-template-override';
    }
    return $classes;
}

/**
 * Get canvas template - creates a full-width template
 */
function aisb_get_canvas_template() {
    // Use WordPress temp directory for better reliability
    $temp_template = get_temp_dir() . 'aisb-canvas-' . get_the_ID() . '-' . time() . '.php';
    
    // Create template content
    $template_content = aisb_generate_canvas_template();
    
    // Write template file
    file_put_contents($temp_template, $template_content);
    
    // Clean up file after use
    add_action('wp_footer', function() use ($temp_template) {
        if (file_exists($temp_template)) {
            unlink($temp_template);
        }
    }, 999);
    
    return $temp_template;
}

/**
 * Generate canvas template content
 * This preserves theme header/footer but removes content area styling
 */
function aisb_generate_canvas_template() {
    ob_start();
    ?>
<?php
/**
 * AI Section Builder Canvas Template
 * This template is generated dynamically to preserve theme compatibility
 * while providing full-width section rendering
 */

// Get theme header (preserves navigation, styles, etc.)
get_header();

// Get our sections data (optimized with caching)
$post_id = get_the_ID();
$sections = aisb_get_sections($post_id);
?>

<div id="aisb-canvas" class="aisb-canvas">
    <style>
        /* Remove theme content constraints */
        .aisb-canvas .site-main,
        .aisb-canvas .entry-content,
        .aisb-canvas .page-content,
        .aisb-canvas .post-content,
        .aisb-canvas .content-area,
        .aisb-canvas main,
        .aisb-canvas article {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide theme page title */
        .aisb-canvas .entry-title,
        .aisb-canvas .page-title,
        .aisb-canvas h1.entry-title {
            display: none !important;
        }
        
        /* Ensure proper width without overflow */
        .aisb-canvas {
            width: 100%;
            overflow-x: hidden;
        }
    </style>
    
    <?php
    // Phase 2A: Enable section rendering for Hero and Features sections
    if (!empty($sections) && is_array($sections)) {
        foreach ($sections as $section) {
            // Render section based on type
            if ($section['type'] === 'hero') {
                echo aisb_render_hero_section($section);
            } elseif ($section['type'] === 'hero-form') {
                echo aisb_render_hero_form_section($section);
            } elseif ($section['type'] === 'features') {
                echo aisb_render_features_section($section);
            } elseif ($section['type'] === 'checklist') {
                echo aisb_render_checklist_section($section);
            } elseif ($section['type'] === 'faq') {
                echo aisb_render_faq_section($section);
            } elseif ($section['type'] === 'stats') {
                echo aisb_render_stats_section($section);
            } elseif ($section['type'] === 'testimonials') {
                echo aisb_render_testimonials_section($section);
            }
        }
    } else {
        // Show placeholder if no sections
        ?>
        <div style="padding: 60px 20px; text-align: center; background: #f5f5f5;">
            <h2>AI Section Builder Active</h2>
            <p>Use the visual editor to add sections to this page.</p>
        </div>
        <?php
    }
    ?>
</div>

<?php
// Get theme footer (preserves footer widgets, scripts, etc.)
get_footer();
?>
    <?php
    return ob_get_clean();
}

/**
 * Migrate old field names to new standardized structure
 */
function aisb_migrate_field_names($content) {
    if (!is_array($content)) {
        return array();
    }
    
    // Don't modify the original array - preserve all fields including media
    $migrated = $content;
    
    // Ensure media fields are preserved
    if (!isset($migrated['media_type']) && isset($content['media_type'])) {
        $migrated['media_type'] = $content['media_type'];
    }
    if (!isset($migrated['video_url']) && isset($content['video_url'])) {
        $migrated['video_url'] = $content['video_url'];
    }
    if (!isset($migrated['featured_image']) && isset($content['featured_image'])) {
        $migrated['featured_image'] = $content['featured_image'];
    }
    
    // Migrate field names
    if (isset($content['eyebrow']) && !isset($content['eyebrow_heading'])) {
        $migrated['eyebrow_heading'] = $content['eyebrow'];
        unset($migrated['eyebrow']);
    }
    if (isset($content['headline']) && !isset($content['heading'])) {
        $migrated['heading'] = $content['headline'];
        unset($migrated['headline']);
    }
    if (isset($content['subheadline']) && !isset($content['content'])) {
        // Wrap in paragraph tags if not already
        $text = $content['subheadline'];
        $migrated['content'] = strpos($text, '<p>') !== false ? $text : '<p>' . $text . '</p>';
        unset($migrated['subheadline']);
    }
    
    // Migrate buttons to global_blocks
    if (isset($content['buttons']) && !isset($content['global_blocks'])) {
        $migrated['global_blocks'] = array_map(function($btn) {
            $btn['type'] = 'button';
            return $btn;
        }, $content['buttons']);
        unset($migrated['buttons']);
    }
    
    // Migrate old single button fields
    if (!empty($content['button_text']) && empty($migrated['global_blocks'])) {
        $migrated['global_blocks'] = array(
            array(
                'type' => 'button',
                'id' => 'btn_migrated_1',
                'text' => $content['button_text'],
                'url' => $content['button_url'] ?? '#',
                'style' => 'primary'
            )
        );
    }
    
    // Migrate media fields to featured_image
    if (isset($content['media_type']) && $content['media_type'] === 'image' && !empty($content['media_image_url'])) {
        $migrated['featured_image'] = $content['media_image_url'];
    }
    
    // Add default variants if not present
    if (!isset($migrated['theme_variant'])) {
        $migrated['theme_variant'] = 'dark';
    }
    if (!isset($migrated['layout_variant'])) {
        $migrated['layout_variant'] = 'content-left';
    }
    
    // Clean up old fields - but NOT our current media fields!
    // DO NOT unset media_type or video_url - we use those!
    unset($migrated['media_image_id']);
    unset($migrated['media_image_url']);
    unset($migrated['media_image_alt']);
    unset($migrated['media_video_type']);
    unset($migrated['button_text']);
    unset($migrated['button_url']);
    
    return $migrated;
}

/**
 * Render Hero section - Standardized field structure
 */
function aisb_render_hero_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Migrate old field names to new structure
    $content = aisb_migrate_field_names($content);
    
    // Debug: Log what we're getting (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AISB Hero Section Content: ' . print_r($content, true));
    }
    
    // Extract standardized fields
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? '');
    $content_text = wp_kses_post($content['content'] ?? '');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Debug: Log extracted media fields
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("AISB Media - Type: $media_type, Image: $featured_image, Video: $video_url");
    }
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'dark');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-hero',
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-hero__container">
            <div class="aisb-hero__grid">
                <div class="aisb-hero__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-hero__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h1 class="aisb-hero__heading"><?php echo $heading; ?></h1>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-hero__body"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    // Render global blocks (buttons for now)
                    $buttons = array_filter($global_blocks, function($block) {
                        return isset($block['type']) && $block['type'] === 'button';
                    });
                    
                    if (!empty($buttons)): ?>
                        <div class="aisb-hero__buttons">
                            <?php foreach ($buttons as $button): ?>
                                <?php if (!empty($button['text'])): ?>
                                    <?php 
                                    $btn_text = esc_html($button['text']);
                                    $btn_url = esc_url($button['url'] ?? '#');
                                    $btn_style = esc_attr($button['style'] ?? 'primary');
                                    $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                                    $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                                    ?>
                                    <a href="<?php echo $btn_url; ?>" 
                                       class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                                       target="<?php echo esc_attr($btn_target); ?>"
                                       <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                        <?php echo $btn_text; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($outro_content): ?>
                        <div class="aisb-hero__outro"><?php echo $outro_content; ?></div>
                    <?php endif; ?>
                </div>
                <?php 
                // Render media based on type
                if ($media_type === 'image' && $featured_image): ?>
                    <div class="aisb-hero__media">
                        <img src="<?php echo $featured_image; ?>" 
                             alt="<?php echo esc_attr($heading); ?>" 
                             class="aisb-hero__image">
                    </div>
                <?php elseif ($media_type === 'video' && $video_url): ?>
                    <div class="aisb-hero__media">
                        <?php 
                        // Check if it's a YouTube URL
                        $is_youtube = preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                        
                        if ($is_youtube && isset($matches[1])): 
                            $youtube_id = $matches[1];
                        ?>
                            <iframe class="aisb-hero__video" 
                                    src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        <?php else: ?>
                            <video class="aisb-hero__video" controls>
                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Hero Form Section
 * Exactly like hero section but without media
 */
function aisb_render_hero_form_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Migrate old field names to new structure
    $content = aisb_migrate_field_names($content);
    
    // Debug: Log what we're getting (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AISB Hero Form Section Content: ' . print_r($content, true));
    }
    
    // Extract standardized fields
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? '');
    $content_text = wp_kses_post($content['content'] ?? '');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Extract form fields
    $form_type = sanitize_text_field($content['form_type'] ?? 'placeholder');
    $form_shortcode = $content['form_shortcode'] ?? '';
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'dark');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-hero-form',
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-hero-form__container">
            <div class="aisb-hero-form__grid">
                <div class="aisb-hero-form__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-hero-form__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h1 class="aisb-hero-form__heading"><?php echo $heading; ?></h1>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-hero-form__body"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    // Render global blocks (buttons for now)
                    $buttons = array_filter($global_blocks, function($block) {
                        return isset($block['type']) && $block['type'] === 'button';
                    });
                    
                    if (!empty($buttons)): ?>
                        <div class="aisb-hero-form__buttons">
                            <?php foreach ($buttons as $button): ?>
                                <?php if (!empty($button['text'])): ?>
                                    <?php 
                                    $btn_text = esc_html($button['text']);
                                    $btn_url = esc_url($button['url'] ?? '#');
                                    $btn_style = esc_attr($button['style'] ?? 'primary');
                                    $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                                    $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                                    ?>
                                    <a href="<?php echo $btn_url; ?>" 
                                       class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                                       target="<?php echo esc_attr($btn_target); ?>"
                                       <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                        <?php echo $btn_text; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($outro_content): ?>
                        <div class="aisb-hero-form__outro"><?php echo $outro_content; ?></div>
                    <?php endif; ?>
                </div>
                <div class="aisb-hero-form__form">
                    <?php 
                    // Render form based on type
                    if ($form_type === 'shortcode' && !empty($form_shortcode)) {
                        // Process shortcode
                        echo do_shortcode($form_shortcode);
                    } else {
                        // Show placeholder form
                        ?>
                        <div class="aisb-form-placeholder">
                            <form class="aisb-placeholder-form">
                                <div class="aisb-form-field">
                                    <input type="text" placeholder="Name" disabled class="aisb-form-input">
                                </div>
                                <div class="aisb-form-field">
                                    <input type="email" placeholder="Email" disabled class="aisb-form-input">
                                </div>
                                <div class="aisb-form-field">
                                    <input type="tel" placeholder="Phone" disabled class="aisb-form-input">
                                </div>
                                <div class="aisb-form-field">
                                    <textarea placeholder="Message" disabled class="aisb-form-textarea" rows="4"></textarea>
                                </div>
                                <div class="aisb-form-field">
                                    <button type="button" class="aisb-btn aisb-btn-primary" disabled>Submit</button>
                                </div>
                            </form>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Features section on frontend
 */
function aisb_render_features_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Debug: Log what we're getting (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AISB Features Section Content: ' . print_r($content, true));
    }
    
    // Extract standardized fields
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? 'Our Features');
    $content_text = wp_kses_post($content['content'] ?? '<p>Discover what makes us different</p>');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
    $card_alignment = sanitize_text_field($content['card_alignment'] ?? 'left');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants - matching Hero structure
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-features', // Section type class - MUST be combined with aisb-section
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant,
        'aisb-features--cards-' . $card_alignment // Card alignment class
    );
    
    // Get media fields
    $featured_image = esc_url($content['featured_image'] ?? '');
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $video_url = esc_url($content['video_url'] ?? '');
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-features__container">
            <!-- Top section with content and optional media (like Hero) -->
            <div class="aisb-features__top">
                <div class="aisb-features__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-features__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h2 class="aisb-features__heading"><?php echo $heading; ?></h2>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-features__intro"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Render media based on type (matching Hero structure)
                if ($media_type === 'image' && $featured_image): ?>
                    <div class="aisb-features__media">
                        <img src="<?php echo $featured_image; ?>" 
                             alt="<?php echo esc_attr($heading); ?>" 
                             class="aisb-features__image">
                    </div>
                <?php elseif ($media_type === 'video' && $video_url): ?>
                    <div class="aisb-features__media">
                        <?php 
                        // Check if it's a YouTube URL
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            // Extract YouTube ID
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                            $youtube_id = isset($matches[1]) ? $matches[1] : '';
                            
                            if ($youtube_id): ?>
                                <iframe class="aisb-features__video" 
                                        src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                        frameborder="0" 
                                        allowfullscreen></iframe>
                            <?php endif;
                        } else {
                            // Direct video file
                            ?>
                            <video class="aisb-features__video" controls>
                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php } ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Feature cards grid -->
            <?php 
            $cards = isset($content['cards']) ? $content['cards'] : array();
            if (!empty($cards)): 
            ?>
                <div class="aisb-features__grid">
                    <?php foreach ($cards as $card): ?>
                        <div class="aisb-features__item">
                            <?php if (!empty($card['image'])): ?>
                                <div class="aisb-features__item-image-wrapper">
                                    <img src="<?php echo esc_url($card['image']); ?>" 
                                         alt="<?php echo esc_attr($card['heading'] ?? ''); ?>" 
                                         class="aisb-features__item-image">
                                </div>
                            <?php endif; ?>
                            
                            <div class="aisb-features__item-content">
                                <?php if (!empty($card['heading'])): ?>
                                    <h3 class="aisb-features__item-title"><?php echo esc_html($card['heading']); ?></h3>
                                <?php endif; ?>
                                
                                <?php if (!empty($card['content'])): ?>
                                    <p class="aisb-features__item-description"><?php echo esc_html($card['content']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($card['link'])): ?>
                                    <?php
                                    $link_text = !empty($card['link_text']) ? esc_html($card['link_text']) : 'Learn More';
                                    $link_target = !empty($card['link_target']) && $card['link_target'] === '_blank' ? '_blank' : '_self';
                                    $link_rel = $link_target === '_blank' ? 'noopener noreferrer' : '';
                                    ?>
                                    <a href="<?php echo esc_url($card['link']); ?>" 
                                       class="aisb-features__item-link"
                                       target="<?php echo esc_attr($link_target); ?>"
                                       <?php if ($link_rel): ?>rel="<?php echo esc_attr($link_rel); ?>"<?php endif; ?>>
                                        <?php echo $link_text; ?> →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php 
            // Render global blocks (buttons for now)
            $buttons = array_filter($global_blocks, function($block) {
                return isset($block['type']) && $block['type'] === 'button';
            });
            
            if (!empty($buttons)): ?>
                <div class="aisb-features__buttons">
                    <?php foreach ($buttons as $button): ?>
                        <?php if (!empty($button['text'])): ?>
                            <?php 
                            $btn_text = esc_html($button['text']);
                            $btn_url = esc_url($button['url'] ?? '#');
                            $btn_style = esc_attr($button['style'] ?? 'primary');
                            $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                            $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                            ?>
                            <a href="<?php echo $btn_url; ?>" 
                               class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                               target="<?php echo esc_attr($btn_target); ?>"
                               <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                <?php echo $btn_text; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($outro_content): ?>
                <div class="aisb-features__outro"><?php echo $outro_content; ?></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Checklist Items
 */
function aisb_render_checklist_items($items) {
    if (empty($items) || !is_array($items)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="aisb-checklist__items">
        <?php foreach ($items as $item): ?>
            <?php 
            $item_heading = esc_html($item['heading'] ?? 'Checklist Item');
            $item_content = esc_html($item['content'] ?? '');
            ?>
            <div class="aisb-checklist__item">
                <div class="aisb-checklist__item-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <path d="M7 12L10 15L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="aisb-checklist__item-content">
                    <h4 class="aisb-checklist__item-heading"><?php echo $item_heading; ?></h4>
                    <?php if ($item_content): ?>
                        <p class="aisb-checklist__item-text"><?php echo $item_content; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Checklist section on frontend
 */
function aisb_render_checklist_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Extract standardized fields (same as Features)
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? 'Everything You Need');
    $content_text = wp_kses_post($content['content'] ?? '<p>Our comprehensive solution includes:</p>');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'content-left');
    
    // Get media fields
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Get global blocks (buttons)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Get items (will be empty in Phase 1)
    $items = isset($content['items']) ? $content['items'] : array();
    
    // Build section classes
    $section_classes = array(
        'aisb-section',
        'aisb-checklist',
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-checklist__container">
            <?php if ($layout_variant !== 'center'): ?>
                <!-- Two-column layout -->
                <div class="aisb-checklist__columns">
                    <!-- Content Column -->
                    <div class="aisb-checklist__content-column">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-checklist__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-checklist__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-checklist__content"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <!-- Checklist Items -->
                        <?php if (!empty($items)): ?>
                            <?php echo aisb_render_checklist_items($items); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($global_blocks)): ?>
                            <div class="aisb-checklist__buttons">
                                <?php foreach ($global_blocks as $block): ?>
                                    <?php if ($block['type'] === 'button'): ?>
                                        <?php
                                        $button_text = esc_html($block['text'] ?? 'Learn More');
                                        $button_url = esc_url($block['url'] ?? '#');
                                        $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                        $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                        $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                        ?>
                                        <a href="<?php echo $button_url; ?>" 
                                           class="<?php echo esc_attr($button_class); ?>"
                                           target="<?php echo esc_attr($button_target); ?>"
                                           <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                            <?php echo $button_text; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($outro_content): ?>
                            <div class="aisb-checklist__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Media Column (only if media exists and not center layout) -->
                    <?php if ($media_type !== 'none'): ?>
                        <div class="aisb-checklist__media-column">
                            <?php if ($media_type === 'image' && $featured_image): ?>
                                <div class="aisb-checklist__media">
                                    <img src="<?php echo $featured_image; ?>" 
                                         alt="<?php echo esc_attr($heading); ?>" 
                                         class="aisb-checklist__image">
                                </div>
                            <?php elseif ($media_type === 'video' && $video_url): ?>
                                <div class="aisb-checklist__media">
                                    <?php
                                    // Check if YouTube
                                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                                        $video_id = $matches[1];
                                        ?>
                                        <iframe class="aisb-checklist__video" 
                                                src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($video_id); ?>" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    <?php } else { ?>
                                        <video class="aisb-checklist__video" controls>
                                            <source src="<?php echo $video_url; ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php } ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Center layout - single column, with media below content (matching hero/features) -->
                <div class="aisb-checklist__center">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-checklist__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h2 class="aisb-checklist__heading"><?php echo $heading; ?></h2>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-checklist__content"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                    
                    <!-- Checklist Items -->
                    <?php if (!empty($items)): ?>
                        <?php echo aisb_render_checklist_items($items); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($global_blocks)): ?>
                        <div class="aisb-checklist__buttons">
                            <?php foreach ($global_blocks as $block): ?>
                                <?php if ($block['type'] === 'button'): ?>
                                    <?php
                                    $button_text = esc_html($block['text'] ?? 'Learn More');
                                    $button_url = esc_url($block['url'] ?? '#');
                                    $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                    $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                    $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                    ?>
                                    <a href="<?php echo $button_url; ?>" 
                                       class="<?php echo esc_attr($button_class); ?>"
                                       target="<?php echo esc_attr($button_target); ?>"
                                       <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                        <?php echo $button_text; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($outro_content): ?>
                        <div class="aisb-checklist__outro"><?php echo $outro_content; ?></div>
                    <?php endif; ?>
                    
                    <!-- Media below content for center layout (matching hero/features) -->
                    <?php if ($media_type !== 'none'): ?>
                        <?php if ($media_type === 'image' && $featured_image): ?>
                            <div class="aisb-checklist__media">
                                <img src="<?php echo $featured_image; ?>" 
                                     alt="<?php echo esc_attr($heading); ?>" 
                                     class="aisb-checklist__image">
                            </div>
                        <?php elseif ($media_type === 'video' && $video_url): ?>
                            <div class="aisb-checklist__media">
                                <?php
                                // Check if YouTube
                                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)): 
                                    $youtube_id = $matches[1];
                                ?>
                                    <iframe class="aisb-checklist__video" 
                                            src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                    </iframe>
                                <?php else: ?>
                                    <video class="aisb-checklist__video" controls>
                                        <source src="<?php echo $video_url; ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render FAQ section
 * @param array $section Section data
 * @return string HTML output
 */
function aisb_render_faq_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Extract standardized fields
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? 'Frequently Asked Questions');
    $content_text = wp_kses_post($content['content'] ?? '<p>Find answers to common questions about our services.</p>');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'center');
    
    // Get media fields
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Get global blocks (buttons)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Get FAQ items (will be empty in Phase 1)
    $faq_items = isset($content['faq_items']) ? $content['faq_items'] : array();
    
    // Build section classes
    $section_classes = array(
        'aisb-section',
        'aisb-faq',
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-faq__container">
            <?php if ($layout_variant !== 'center'): ?>
                <!-- Two-column layout -->
                <div class="aisb-faq__columns">
                    <!-- Content Column -->
                    <div class="aisb-faq__content-column">
                        <?php if ($eyebrow_heading): ?>
                            <div class="aisb-faq__eyebrow"><?php echo $eyebrow_heading; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($heading): ?>
                            <h2 class="aisb-faq__heading"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        
                        <?php if ($content_text): ?>
                            <div class="aisb-faq__content"><?php echo $content_text; ?></div>
                        <?php endif; ?>
                        
                        <!-- FAQ Items with Accordion -->
                        <?php if (!empty($faq_items)): ?>
                            <div class="aisb-faq__items">
                                <?php foreach ($faq_items as $index => $item): ?>
                                    <div class="aisb-faq__item" data-faq-index="<?php echo $index; ?>">
                                        <?php if (!empty($item['question'])): ?>
                                            <h3 class="aisb-faq__item-question" data-faq-toggle="<?php echo $index; ?>"><?php echo esc_html($item['question']); ?></h3>
                                        <?php endif; ?>
                                        <?php if (!empty($item['answer'])): ?>
                                            <div class="aisb-faq__item-answer" data-faq-content="<?php echo $index; ?>">
                                                <div class="aisb-faq__item-answer-inner">
                                                    <?php echo wp_kses_post($item['answer']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($global_blocks)): ?>
                            <div class="aisb-faq__buttons">
                                <?php foreach ($global_blocks as $block): ?>
                                    <?php if ($block['type'] === 'button'): ?>
                                        <?php
                                        $button_text = esc_html($block['text'] ?? 'Learn More');
                                        $button_url = esc_url($block['url'] ?? '#');
                                        $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                        $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                        $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                        ?>
                                        <a href="<?php echo $button_url; ?>" 
                                           class="<?php echo esc_attr($button_class); ?>"
                                           target="<?php echo esc_attr($button_target); ?>"
                                           <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                            <?php echo $button_text; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($outro_content): ?>
                            <div class="aisb-faq__outro"><?php echo $outro_content; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Media Column (only if media exists and not center layout) -->
                    <?php if ($media_type !== 'none'): ?>
                        <div class="aisb-faq__media-column">
                            <?php if ($media_type === 'image' && $featured_image): ?>
                                <div class="aisb-faq__media">
                                    <img src="<?php echo $featured_image; ?>" 
                                         alt="<?php echo esc_attr($heading); ?>" 
                                         class="aisb-faq__image">
                                </div>
                            <?php elseif ($media_type === 'video' && $video_url): ?>
                                <div class="aisb-faq__media">
                                    <?php
                                    // Check if YouTube
                                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                                        $video_id = $matches[1];
                                        ?>
                                        <iframe class="aisb-faq__video" 
                                                src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($video_id); ?>" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    <?php } else { ?>
                                        <video class="aisb-faq__video" controls>
                                            <source src="<?php echo $video_url; ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php } ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Center layout - single column, with media below content -->
                <div class="aisb-faq__center">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-faq__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h2 class="aisb-faq__heading"><?php echo $heading; ?></h2>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-faq__content"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                    
                    <!-- FAQ Items with Accordion -->
                    <?php if (!empty($faq_items)): ?>
                        <div class="aisb-faq__items">
                            <?php foreach ($faq_items as $index => $item): ?>
                                <div class="aisb-faq__item" data-faq-index="<?php echo $index; ?>">
                                    <?php if (!empty($item['question'])): ?>
                                        <h3 class="aisb-faq__item-question" data-faq-toggle="<?php echo $index; ?>"><?php echo esc_html($item['question']); ?></h3>
                                    <?php endif; ?>
                                    <?php if (!empty($item['answer'])): ?>
                                        <div class="aisb-faq__item-answer" data-faq-content="<?php echo $index; ?>">
                                            <div class="aisb-faq__item-answer-inner">
                                                <?php echo wp_kses_post($item['answer']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($global_blocks)): ?>
                        <div class="aisb-faq__buttons">
                            <?php foreach ($global_blocks as $block): ?>
                                <?php if ($block['type'] === 'button'): ?>
                                    <?php
                                    $button_text = esc_html($block['text'] ?? 'Learn More');
                                    $button_url = esc_url($block['url'] ?? '#');
                                    $button_target = ($block['target'] === '_blank') ? '_blank' : '_self';
                                    $button_style = sanitize_text_field($block['style'] ?? 'primary');
                                    $button_class = $button_style === 'primary' ? 'aisb-btn aisb-btn-primary' : 'aisb-btn aisb-btn-secondary';
                                    ?>
                                    <a href="<?php echo $button_url; ?>" 
                                       class="<?php echo esc_attr($button_class); ?>"
                                       target="<?php echo esc_attr($button_target); ?>"
                                       <?php echo $button_target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
                                        <?php echo $button_text; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($outro_content): ?>
                        <div class="aisb-faq__outro"><?php echo $outro_content; ?></div>
                    <?php endif; ?>
                    
                    <!-- Media below content for center layout -->
                    <?php if ($media_type !== 'none'): ?>
                        <?php if ($media_type === 'image' && $featured_image): ?>
                            <div class="aisb-faq__media">
                                <img src="<?php echo $featured_image; ?>" 
                                     alt="<?php echo esc_attr($heading); ?>" 
                                     class="aisb-faq__image">
                            </div>
                        <?php elseif ($media_type === 'video' && $video_url): ?>
                            <div class="aisb-faq__media">
                                <?php
                                // Check if YouTube
                                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)): 
                                    $youtube_id = $matches[1];
                                ?>
                                    <iframe class="aisb-faq__video" 
                                            src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                    </iframe>
                                <?php else: ?>
                                    <video class="aisb-faq__video" controls>
                                        <source src="<?php echo $video_url; ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Stats section on frontend
 * Phase 1: Core structure without repeatable stats items
 */
function aisb_render_stats_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Debug: Log what we're getting (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AISB Stats Section Content: ' . print_r($content, true));
    }
    
    // Extract standardized fields (following Features pattern)
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? 'By the Numbers');
    $content_text = wp_kses_post($content['content'] ?? '<p>Our impact and achievements</p>');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Media fields - matching Features section
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'center');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants - matching Features structure
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-stats',    // Section type class - MUST be combined with aisb-section
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-stats__container">
            <!-- Top section with content (like Features) -->
            <div class="aisb-stats__top">
                <div class="aisb-stats__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-stats__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h2 class="aisb-stats__heading"><?php echo $heading; ?></h2>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-stats__intro"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Render media based on type (matching Features structure exactly)
                if ($media_type === 'image' && $featured_image): ?>
                    <div class="aisb-stats__media">
                        <img src="<?php echo $featured_image; ?>" 
                             alt="<?php echo esc_attr($heading); ?>" 
                             class="aisb-stats__image">
                    </div>
                <?php elseif ($media_type === 'video' && $video_url): ?>
                    <div class="aisb-stats__media">
                        <?php 
                        // Check if it's a YouTube URL
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            // Extract YouTube ID
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                            $youtube_id = isset($matches[1]) ? $matches[1] : '';
                            
                            if ($youtube_id): ?>
                                <iframe class="aisb-stats__video" 
                                        src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                        frameborder="0" 
                                        allowfullscreen></iframe>
                            <?php endif;
                        } else {
                            // Self-hosted video or other video source
                            ?>
                            <video controls class="aisb-stats__video">
                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php } ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Stats grid -->
            <div class="aisb-stats__grid">
                <?php 
                // Get stats items array
                $stats = isset($content['stats']) && is_array($content['stats']) ? $content['stats'] : array();
                
                if (!empty($stats)):
                    foreach ($stats as $stat):
                        $stat_number = esc_html($stat['number'] ?? '');
                        $stat_label = esc_html($stat['label'] ?? '');
                        $stat_description = esc_html($stat['description'] ?? '');
                        
                        if ($stat_number || $stat_label): // Only render if there's content
                ?>
                    <div class="aisb-stats__item">
                        <?php if ($stat_number): ?>
                            <div class="aisb-stats__item-number"><?php echo $stat_number; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($stat_label): ?>
                            <div class="aisb-stats__item-label"><?php echo $stat_label; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($stat_description): ?>
                            <div class="aisb-stats__item-description"><?php echo $stat_description; ?></div>
                        <?php endif; ?>
                    </div>
                <?php 
                        endif;
                    endforeach;
                else:
                    // Show placeholder stats if no actual stats
                ?>
                    <div class="aisb-stats__item">
                        <div class="aisb-stats__item-number">99%</div>
                        <div class="aisb-stats__item-label">Customer Satisfaction</div>
                        <div class="aisb-stats__item-description">Based on 10,000+ reviews</div>
                    </div>
                    <div class="aisb-stats__item">
                        <div class="aisb-stats__item-number">50M+</div>
                        <div class="aisb-stats__item-label">Active Users</div>
                        <div class="aisb-stats__item-description">Across 120 countries</div>
                    </div>
                    <div class="aisb-stats__item">
                        <div class="aisb-stats__item-number">24/7</div>
                        <div class="aisb-stats__item-label">Support Available</div>
                        <div class="aisb-stats__item-description">Always here to help</div>
                    </div>
                    <div class="aisb-stats__item">
                        <div class="aisb-stats__item-number">4.9★</div>
                        <div class="aisb-stats__item-label">Average Rating</div>
                        <div class="aisb-stats__item-description">From industry experts</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Render global blocks (buttons for now)
            $buttons = array_filter($global_blocks, function($block) {
                return isset($block['type']) && $block['type'] === 'button';
            });
            
            if (!empty($buttons)): ?>
                <div class="aisb-stats__buttons">
                    <?php foreach ($buttons as $button): ?>
                        <?php if (!empty($button['text'])): ?>
                            <?php 
                            $btn_text = esc_html($button['text']);
                            $btn_url = esc_url($button['url'] ?? '#');
                            $btn_style = esc_attr($button['style'] ?? 'primary');
                            $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                            $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                            ?>
                            <a href="<?php echo $btn_url; ?>" 
                               class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                               target="<?php echo esc_attr($btn_target); ?>"
                               <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                <?php echo $btn_text; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($outro_content): ?>
                <div class="aisb-stats__outro"><?php echo $outro_content; ?></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Testimonials section
 */
function aisb_render_testimonials_section($section) {
    // Handle both old and new section structure
    $content = isset($section['content']) ? $section['content'] : $section;
    
    // Debug: Log what we're getting (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AISB Testimonials Section Content: ' . print_r($content, true));
    }
    
    // Extract standardized fields (following Features pattern)
    $eyebrow_heading = esc_html($content['eyebrow_heading'] ?? '');
    $heading = esc_html($content['heading'] ?? 'What Our Customers Say');
    $content_text = wp_kses_post($content['content'] ?? '<p>Hear from real people who have achieved amazing results with our solution.</p>');
    $outro_content = wp_kses_post($content['outro_content'] ?? '');
    
    // Media fields - matching Features section
    $media_type = sanitize_text_field($content['media_type'] ?? 'none');
    $featured_image = esc_url($content['featured_image'] ?? '');
    $video_url = esc_url($content['video_url'] ?? '');
    
    // Get variants
    $theme_variant = sanitize_text_field($content['theme_variant'] ?? 'light');
    $layout_variant = sanitize_text_field($content['layout_variant'] ?? 'center');
    
    // Get global blocks (buttons for now)
    $global_blocks = isset($content['global_blocks']) ? $content['global_blocks'] : array();
    
    // Build section classes based on variants - matching Features structure
    $section_classes = array(
        'aisb-section',  // Base class required for theme inheritance
        'aisb-testimonials',    // Section type class - MUST be combined with aisb-section
        'aisb-section--' . $theme_variant,
        'aisb-section--' . $layout_variant
    );
    
    ob_start();
    ?>
    <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
        <div class="aisb-testimonials__container">
            <!-- Top section with content (like Features) -->
            <div class="aisb-testimonials__top">
                <div class="aisb-testimonials__content">
                    <?php if ($eyebrow_heading): ?>
                        <div class="aisb-testimonials__eyebrow"><?php echo $eyebrow_heading; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($heading): ?>
                        <h2 class="aisb-testimonials__heading"><?php echo $heading; ?></h2>
                    <?php endif; ?>
                    
                    <?php if ($content_text): ?>
                        <div class="aisb-testimonials__intro"><?php echo $content_text; ?></div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Render media based on type (matching Features structure exactly)
                if ($media_type === 'image' && $featured_image): ?>
                    <div class="aisb-testimonials__media">
                        <img src="<?php echo $featured_image; ?>" 
                             alt="<?php echo esc_attr($heading); ?>" 
                             class="aisb-testimonials__image">
                    </div>
                <?php elseif ($media_type === 'video' && $video_url): ?>
                    <div class="aisb-testimonials__media">
                        <?php 
                        // Check if it's a YouTube URL
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            // Extract YouTube ID
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
                            $youtube_id = isset($matches[1]) ? $matches[1] : '';
                            
                            if ($youtube_id): ?>
                                <iframe class="aisb-testimonials__video" 
                                        src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" 
                                        frameborder="0" 
                                        allowfullscreen></iframe>
                            <?php endif;
                        } else {
                            // Self-hosted video or other video source
                            ?>
                            <video controls class="aisb-testimonials__video">
                                <source src="<?php echo $video_url; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php } ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Testimonials grid -->
            <?php 
            // Get testimonials array
            $testimonials = isset($content['testimonials']) && is_array($content['testimonials']) ? $content['testimonials'] : array();
            
            if (!empty($testimonials)): ?>
                <div class="aisb-testimonials__grid">
                    <?php foreach ($testimonials as $testimonial): 
                        $rating = isset($testimonial['rating']) ? intval($testimonial['rating']) : 5;
                        $quote = isset($testimonial['content']) ? esc_html($testimonial['content']) : '';
                        $author_name = isset($testimonial['author_name']) ? esc_html($testimonial['author_name']) : 'Anonymous';
                        $author_title = isset($testimonial['author_title']) ? esc_html($testimonial['author_title']) : '';
                        $author_image = isset($testimonial['author_image']) ? esc_url($testimonial['author_image']) : '';
                    ?>
                        <div class="aisb-testimonials__item">
                            <div class="aisb-testimonials__rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $rating): ?>
                                        <span class="aisb-testimonials__star aisb-testimonials__star--filled">★</span>
                                    <?php else: ?>
                                        <span class="aisb-testimonials__star">☆</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php if ($quote): ?>
                                <div class="aisb-testimonials__quote">
                                    "<?php echo $quote; ?>"
                                </div>
                            <?php endif; ?>
                            <div class="aisb-testimonials__author">
                                <?php if ($author_image): ?>
                                    <img src="<?php echo $author_image; ?>" 
                                         alt="<?php echo $author_name; ?>" 
                                         class="aisb-testimonials__author-image">
                                <?php endif; ?>
                                <div class="aisb-testimonials__author-info">
                                    <div class="aisb-testimonials__author-name">
                                        <?php echo $author_name; ?>
                                    </div>
                                    <?php if ($author_title): ?>
                                        <div class="aisb-testimonials__author-title">
                                            <?php echo $author_title; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="aisb-testimonials__grid">
                    <div class="aisb-testimonials__placeholder">
                        <p>Testimonials coming soon! Add your first testimonial to get started.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php 
            // Render global blocks (buttons for now)
            $buttons = array_filter($global_blocks, function($block) {
                return isset($block['type']) && $block['type'] === 'button';
            });
            
            if (!empty($buttons)): ?>
                <div class="aisb-testimonials__buttons">
                    <?php foreach ($buttons as $button): ?>
                        <?php if (!empty($button['text'])): ?>
                            <?php 
                            $btn_text = esc_html($button['text']);
                            $btn_url = esc_url($button['url'] ?? '#');
                            $btn_style = esc_attr($button['style'] ?? 'primary');
                            $btn_target = isset($button['target']) && $button['target'] === '_blank' ? '_blank' : '_self';
                            $btn_rel = $btn_target === '_blank' ? 'noopener noreferrer' : '';
                            ?>
                            <a href="<?php echo $btn_url; ?>" 
                               class="aisb-btn aisb-btn-<?php echo $btn_style; ?>"
                               target="<?php echo esc_attr($btn_target); ?>"
                               <?php if ($btn_rel): ?>rel="<?php echo esc_attr($btn_rel); ?>"<?php endif; ?>>
                                <?php echo $btn_text; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($outro_content): ?>
                <div class="aisb-testimonials__outro"><?php echo $outro_content; ?></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue frontend styles
 */
function aisb_enqueue_styles() {
    // Only enqueue if we have active sections
    if (!aisb_has_sections()) {
        return;
    }
    
    // Enqueue core design tokens first
    wp_enqueue_style(
        'aisb-tokens',
        AISB_PLUGIN_URL . 'assets/css/core/00-tokens.css',
        array(),
        AISB_VERSION
    );
    
    // Enqueue base architecture - CRITICAL for theme inheritance
    wp_enqueue_style(
        'aisb-base',
        AISB_PLUGIN_URL . 'assets/css/core/01-base.css',
        array('aisb-tokens'),
        AISB_VERSION
    );
    
    // Enqueue utility classes
    wp_enqueue_style(
        'aisb-utilities',
        AISB_PLUGIN_URL . 'assets/css/core/02-utilities.css',
        array('aisb-base'), // Now depends on base, not just tokens
        AISB_VERSION
    );
    
    // For now, load hero section styles (will be dynamic in future)
    wp_enqueue_style(
        'aisb-section-hero',
        AISB_PLUGIN_URL . 'assets/css/sections/hero.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load hero-form section styles
    wp_enqueue_style(
        'aisb-section-hero-form',
        AISB_PLUGIN_URL . 'assets/css/sections/hero-form.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load features section styles
    wp_enqueue_style(
        'aisb-section-features',
        AISB_PLUGIN_URL . 'assets/css/sections/features.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load checklist section styles
    wp_enqueue_style(
        'aisb-section-checklist',
        AISB_PLUGIN_URL . 'assets/css/sections/checklist.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load FAQ section styles
    wp_enqueue_style(
        'aisb-section-faq',
        AISB_PLUGIN_URL . 'assets/css/sections/faq.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load Stats section styles
    wp_enqueue_style(
        'aisb-section-stats',
        AISB_PLUGIN_URL . 'assets/css/sections/stats.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load Testimonials section styles
    wp_enqueue_style(
        'aisb-section-testimonials',
        AISB_PLUGIN_URL . 'assets/css/sections/testimonials.css',
        array('aisb-utilities'),
        AISB_VERSION
    );
    
    // Load FAQ accordion JavaScript (Vanilla JS version - no jQuery dependency)
    wp_enqueue_script(
        'aisb-faq-accordion',
        AISB_PLUGIN_URL . 'assets/js/frontend/faq-accordion-vanilla.js',
        array(), // No dependencies
        AISB_VERSION,
        true
    );
    
    // NO INLINE CSS NEEDED - The CSS architecture handles everything via context variables
    // Removed hardcoded color overrides that were fighting with the CSS variable system
}

/**
 * Enqueue admin styles
 */
function aisb_enqueue_admin_styles($hook) {
    // Load on our admin pages
    if (strpos($hook, 'ai-section-builder') !== false) {
        wp_enqueue_style(
            'aisb-admin-styles',
            AISB_PLUGIN_URL . 'assets/css/admin/admin-styles.css',
            ['wp-admin'],
            AISB_VERSION
        );
    }
    
    // Load editor styles on editor page
    if ($hook === 'admin_page_aisb-editor') {
        // Load EXACT SAME core architecture as frontend for consistency
        wp_enqueue_style(
            'aisb-tokens',
            AISB_PLUGIN_URL . 'assets/css/core/00-tokens.css',
            [],
            AISB_VERSION
        );
        
        // CRITICAL: Add base architecture (was missing!)
        wp_enqueue_style(
            'aisb-base',
            AISB_PLUGIN_URL . 'assets/css/core/01-base.css',
            ['aisb-tokens'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-utilities',
            AISB_PLUGIN_URL . 'assets/css/core/02-utilities.css',
            ['aisb-base'], // Now depends on base, matching frontend
            AISB_VERSION
        );
        
        // Load section styles (same as frontend for consistency)
        wp_enqueue_style(
            'aisb-section-hero',
            AISB_PLUGIN_URL . 'assets/css/sections/hero.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-hero-form',
            AISB_PLUGIN_URL . 'assets/css/sections/hero-form.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-features',
            AISB_PLUGIN_URL . 'assets/css/sections/features.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-checklist',
            AISB_PLUGIN_URL . 'assets/css/sections/checklist.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-faq',
            AISB_PLUGIN_URL . 'assets/css/sections/faq.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-stats',
            AISB_PLUGIN_URL . 'assets/css/sections/stats.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        wp_enqueue_style(
            'aisb-section-testimonials',
            AISB_PLUGIN_URL . 'assets/css/sections/testimonials.css',
            ['aisb-utilities'],
            AISB_VERSION
        );
        
        // Then load editor UI styles (toolbar, panels, etc. - NOT section styles)
        wp_enqueue_style(
            'aisb-editor-styles',
            AISB_PLUGIN_URL . 'assets/css/editor/editor-styles.css',
            ['aisb-section-hero', 'aisb-section-features', 'aisb-section-checklist', 'aisb-section-faq', 'aisb-section-stats', 'aisb-section-testimonials'], // Depends on section styles
            AISB_VERSION
        );
        // Enqueue Sortable.js from CDN with local fallback
        wp_enqueue_script(
            'sortablejs-cdn',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );
        
        // Add local fallback for Sortable.js
        wp_scripts()->add_inline_script(
            'sortablejs-cdn',
            'window.Sortable || document.write("<script src=\"' . 
            AISB_PLUGIN_URL . 'assets/js/vendor/sortable.min.js\">\\x3C/script>");',
            'after'
        );
        
        // Enqueue WordPress media scripts for image/video selection
        wp_enqueue_media();
        
        // Enqueue WordPress editor scripts for TinyMCE/WYSIWYG
        wp_enqueue_editor();
        
        // Enqueue jQuery UI Autocomplete (built into WordPress)
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-autocomplete');
        
        // Enqueue repeater field module
        wp_enqueue_script(
            'aisb-repeater-field',
            AISB_PLUGIN_URL . 'assets/js/editor/repeater-field.js',
            ['jquery', 'sortablejs-cdn'],
            AISB_VERSION,
            true
        );
        
        wp_enqueue_script(
            'aisb-editor-script',
            AISB_PLUGIN_URL . 'assets/js/editor/editor.js',
            ['jquery', 'sortablejs-cdn', 'aisb-repeater-field'],
            AISB_VERSION,
            true
        );
        
        // Enqueue color settings JavaScript
        wp_enqueue_script(
            'aisb-color-settings',
            AISB_PLUGIN_URL . 'assets/js/admin/color-settings.js',
            ['jquery', 'aisb-editor-script'],
            AISB_VERSION,
            true
        );
        
        // Localize color settings script with nonce
        wp_localize_script('aisb-color-settings', 'aisbColorSettings', [
            'nonce' => wp_create_nonce('aisb_color_settings')
        ]);
        
        // Localize script with settings for drag-drop functionality
        wp_localize_script('aisb-editor-script', 'aisbEditor', array(
            'nonce' => wp_create_nonce('aisb_editor_nonce'),
            'aisbNonce' => wp_create_nonce('aisb_nonce'), // For global settings AJAX
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('aisb/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'features' => array(
                'dragDrop' => true, // Now enabled with Sortable.js
                'autoSave' => true,
                'keyboardNav' => true
            ),
            'settings' => array(
                'autoSaveDelay' => 2000,
                'maxRetries' => 3,
                'timeoutDuration' => 30000
            ),
            'i18n' => array(
                'reorderMode' => __('Reorder mode activated. Use arrow keys to move section, Enter to confirm, Escape to cancel.', 'ai-section-builder'),
                'sectionMoved' => __('Section moved successfully', 'ai-section-builder'),
                'reorderCancelled' => __('Reorder cancelled', 'ai-section-builder'),
                'autoSaved' => __('Changes saved automatically', 'ai-section-builder'),
                'saveFailed' => __('Save failed', 'ai-section-builder'),
                'networkError' => __('Network error. Please check your connection.', 'ai-section-builder'),
                'confirmDelete' => __('Are you sure you want to delete this section?', 'ai-section-builder')
            )
        ));
        
        // Add body class for editor
        add_filter('admin_body_class', function($classes) {
            return $classes . ' aisb-editor-active';
        });
    }
    
    // Load on post/page edit screens for meta box
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        $screen = get_current_screen();
        if ($screen && in_array($screen->post_type, ['post', 'page'])) {
            wp_enqueue_style(
                'aisb-admin-styles',
                AISB_PLUGIN_URL . 'assets/css/admin/admin-styles.css',
                ['wp-admin'],
                AISB_VERSION
            );
        }
    }
}

/**
 * Detect active page builders on a post
 * 
 * @param int $post_id Post ID to check
 * @return array Array of active builder slugs
 */
function aisb_detect_active_builders($post_id) {
    $builders = [];
    
    // Elementor
    if (get_post_meta($post_id, '_elementor_data', true)) {
        $builders[] = 'elementor';
    }
    
    // Beaver Builder
    if (get_post_meta($post_id, '_fl_builder_data', true)) {
        $builders[] = 'beaver-builder';
    }
    
    // Divi
    if (get_post_meta($post_id, '_et_pb_use_builder', true) === 'on') {
        $builders[] = 'divi';
    }
    
    // WPBakery (Visual Composer)
    if (get_post_meta($post_id, '_wpb_vc_js_status', true)) {
        $builders[] = 'wpbakery';
    }
    
    // Breakdance
    if (get_post_meta($post_id, '_breakdance_data', true)) {
        $builders[] = 'breakdance';
    }
    
    // Bricks
    if (get_post_meta($post_id, '_bricks_page_content_2', true)) {
        $builders[] = 'bricks';
    }
    
    // Our plugin
    if (aisb_is_enabled($post_id)) {
        $builders[] = 'aisb';
    }
    
    return $builders;
}

/**
 * Get friendly name for page builder
 * 
 * @param string $builder Builder slug
 * @return string Friendly name
 */
function aisb_get_builder_name($builder) {
    $names = [
        'elementor' => 'Elementor',
        'beaver-builder' => 'Beaver Builder',
        'divi' => 'Divi Builder',
        'wpbakery' => 'WPBakery Page Builder',
        'breakdance' => 'Breakdance',
        'bricks' => 'Bricks Builder',
        'aisb' => 'AI Section Builder'
    ];
    
    return $names[$builder] ?? ucfirst($builder);
}

/**
 * Check if AI Section Builder is enabled for a post (optimized with caching)
 * 
 * @param int $post_id Post ID
 * @return bool
 */
function aisb_is_enabled($post_id) {
    $cache_key = 'aisb_enabled_' . $post_id;
    
    // Check cache first
    $cached = wp_cache_get($cache_key, 'aisb');
    if ($cached !== false) {
        return (bool) $cached;
    }
    
    // Check explicit enable flag ONLY
    // Phase 1B: Don't check sections since we don't create them yet
    $enabled = get_post_meta($post_id, '_aisb_enabled', true);
    
    // Must be explicitly enabled (1 or '1')
    $result = ($enabled == 1);
    
    // Cache for 1 hour
    wp_cache_set($cache_key, $result ? 1 : 0, 'aisb', HOUR_IN_SECONDS);
    
    return $result;
}

/**
 * Check if current page has sections
 */
function aisb_has_sections() {
    if (!is_singular(['post', 'page'])) {
        return false;
    }
    
    return aisb_is_enabled(get_the_ID());
}

/**
 * Check if there are builder conflicts on a post
 * 
 * @param int $post_id Post ID
 * @return array Array with conflict info
 */
function aisb_check_conflicts($post_id) {
    $active_builders = aisb_detect_active_builders($post_id);
    $conflicts = array_diff($active_builders, ['aisb']);
    
    return [
        'has_conflicts' => !empty($conflicts),
        'conflicting_builders' => $conflicts,
        'all_builders' => $active_builders
    ];
}

/**
 * Display admin notices for conflicts and other issues
 */
function aisb_admin_notices() {
    $screen = get_current_screen();
    
    // Only show on post edit screens
    if (!$screen || !in_array($screen->id, ['post', 'page'])) {
        return;
    }
    
    // Check for notices
    if (isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        
        // Check for conflict notices
        $conflicts = get_transient('aisb_conflict_notice_' . $post_id);
        if ($conflicts) {
            $builder_names = array_map('aisb_get_builder_name', $conflicts);
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Template override disabled due to conflicts with:', 'ai-section-builder'); ?>
                    <strong><?php echo esc_html(implode(', ', $builder_names)); ?></strong>
                </p>
                <p>
                    <a href="#aisb-meta-box" class="button button-secondary">
                        <?php _e('Resolve Conflict', 'ai-section-builder'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('aisb_conflict_notice_' . $post_id);
        }
        
        // Check for deactivation success notice
        if (get_transient('aisb_deactivated_' . $post_id)) {
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Page builder has been deactivated. Your sections have been preserved and will be restored if you reactivate.', 'ai-section-builder'); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_deactivated_' . $post_id);
        }
        
        // Check for activation success notice
        if (get_transient('aisb_activated_' . $post_id)) {
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php _e('Successfully activated! You can now edit with the AI Section Builder.', 'ai-section-builder'); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_activated_' . $post_id);
        }
        
        // Check for switch success notice
        $switched_from = get_transient('aisb_switched_' . $post_id);
        if ($switched_from && !empty($switched_from)) {
            $builder_names = array_map('aisb_get_builder_name', $switched_from);
            ?>
            <div class="aisb-notice aisb-notice-success">
                <p>
                    <strong><?php _e('AI Section Builder:', 'ai-section-builder'); ?></strong>
                    <?php printf(
                        _n(
                            'Successfully switched from %s. Your original content has been preserved.',
                            'Successfully switched from %s. Your original content has been preserved.',
                            count($builder_names),
                            'ai-section-builder'
                        ),
                        '<strong>' . implode(', ', $builder_names) . '</strong>'
                    ); ?>
                </p>
            </div>
            <?php
            delete_transient('aisb_switched_' . $post_id);
        }
    }
}

/**
 * Debug function - call this to see current state of a post
 * Usage: Add ?aisb_debug=1 to any post URL to see debug info
 */
function aisb_debug_post_state() {
    // Only for logged in admins with debug query param
    if (!current_user_can('manage_options') || !isset($_GET['aisb_debug'])) {
        return;
    }
    
    if (!is_singular(['post', 'page'])) {
        return;
    }
    
    $post_id = get_the_ID();
    
    echo '<div style="position: fixed; top: 0; right: 0; background: #000; color: #0f0; padding: 20px; font-family: monospace; font-size: 12px; z-index: 99999; max-width: 400px; overflow: auto; max-height: 100vh;">';
    echo '<h3 style="color: #fff; margin-top: 0;">AISB Debug - Post ' . $post_id . '</h3>';
    
    echo '<strong>Meta Data:</strong><br>';
    echo '_aisb_enabled: ' . var_export(get_post_meta($post_id, '_aisb_enabled', true), true) . '<br>';
    echo '_aisb_sections: ' . htmlspecialchars(print_r(get_post_meta($post_id, '_aisb_sections', true), true)) . '<br>';
    
    echo '<strong>Builder Detection:</strong><br>';
    $active_builders = aisb_detect_active_builders($post_id);
    echo 'Active builders: ' . implode(', ', $active_builders) . '<br>';
    
    $conflict_check = aisb_check_conflicts($post_id);
    echo 'Has conflicts: ' . var_export($conflict_check['has_conflicts'], true) . '<br>';
    echo 'Conflicting builders: ' . implode(', ', $conflict_check['conflicting_builders']) . '<br>';
    
    echo '<strong>Function Results:</strong><br>';
    echo 'aisb_is_enabled(): ' . var_export(aisb_is_enabled($post_id), true) . '<br>';
    echo 'aisb_has_sections(): ' . var_export(aisb_has_sections(), true) . '<br>';
    
    echo '<strong>Template Override:</strong><br>';
    echo 'is_singular: ' . var_export(is_singular(['post', 'page']), true) . '<br>';
    echo 'body_class: ' . implode(' ', get_body_class()) . '<br>';
    
    echo '<strong>Cache Check:</strong><br>';
    echo 'sections cache: ' . htmlspecialchars(print_r(wp_cache_get('aisb_sections_' . $post_id, 'aisb'), true)) . '<br>';
    echo 'enabled cache: ' . htmlspecialchars(print_r(wp_cache_get('aisb_enabled_' . $post_id, 'aisb'), true)) . '<br>';
    
    echo '</div>';
}
add_action('wp_footer', 'aisb_debug_post_state');

/**
 * Migration: Clean up old Phase 1A hero data
 * This runs once to clean up test data from Phase 1A development
 */
function aisb_migrate_cleanup_old_data() {
    // Check if migration has already run
    $migration_version = get_option('aisb_migration_version', 0);
    
    if ($migration_version < 1) {
        global $wpdb;
        
        // Find all posts with old hero section data
        $posts_with_sections = $wpdb->get_col(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_aisb_sections'"
        );
        
        foreach ($posts_with_sections as $post_id) {
            $sections = get_post_meta($post_id, '_aisb_sections', true);
            
            // Check if this contains old Phase 1A hero data
            if (is_array($sections)) {
                $has_old_hero = false;
                foreach ($sections as $section) {
                    if (isset($section['type']) && $section['type'] === 'hero' && 
                        (isset($section['headline']) || isset($section['subheadline']))) {
                        $has_old_hero = true;
                        break;
                    }
                }
                
                // If old hero data found, clear it
                if ($has_old_hero) {
                    delete_post_meta($post_id, '_aisb_sections');
                    
                    // Also clear enabled flag if no editor is ready yet
                    // Phase 1B doesn't create sections, so having enabled without sections is invalid
                    update_post_meta($post_id, '_aisb_enabled', 0);
                    
                    // Clear cache
                    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
                    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
                    
                    error_log("AISB Migration: Cleaned old hero data from post $post_id");
                }
            }
        }
        
        // Mark migration as complete
        update_option('aisb_migration_version', 1);
        error_log("AISB Migration: Phase 1A cleanup complete");
    }
}

/**
 * Database performance optimizations
 */

/**
 * Add database indexes for better performance
 */
function aisb_add_database_indexes() {
    global $wpdb;
    
    // Add indexes for our meta keys to improve query performance
    $indexes = [
        "_aisb_enabled",
        "_aisb_sections", 
        "_aisb_original_content",
        "_aisb_switched_from"
    ];
    
    foreach ($indexes as $meta_key) {
        $wpdb->query($wpdb->prepare(
            "ALTER TABLE {$wpdb->postmeta} 
             ADD INDEX IF NOT EXISTS aisb_{$meta_key} (meta_key(20), meta_value(10))",
            $meta_key
        ));
    }
}

/**
 * Optimized function to get sections with caching
 * 
 * @param int $post_id Post ID
 * @param bool $use_cache Whether to use cache
 * @return array|false Sections data or false
 */
function aisb_get_sections($post_id, $use_cache = true) {
    $cache_key = 'aisb_sections_' . $post_id;
    
    if ($use_cache) {
        $cached = wp_cache_get($cache_key, 'aisb');
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $sections = get_post_meta($post_id, '_aisb_sections', true);
    
    // Validate and sanitize sections data
    if (!is_array($sections)) {
        $sections = [];
    }
    
    // Cache for 1 hour
    if ($use_cache) {
        wp_cache_set($cache_key, $sections, 'aisb', HOUR_IN_SECONDS);
    }
    
    return $sections;
}

/**
 * Optimized function to update sections with cache invalidation
 * 
 * @param int $post_id Post ID
 * @param array $sections Sections data
 * @return bool Success
 */
function aisb_update_sections($post_id, $sections) {
    // Validate sections data
    if (!is_array($sections)) {
        return false;
    }
    
    // Sanitize sections data
    $sections = aisb_sanitize_sections($sections);
    
    // Update post meta
    $result = update_post_meta($post_id, '_aisb_sections', $sections);
    
    // Invalidate cache
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
    
    return $result !== false;
}

/**
 * Sanitize sections data for security and performance
 * 
 * @param array $sections Raw sections data
 * @return array Sanitized sections data
 */
function aisb_sanitize_sections($sections) {
    if (!is_array($sections)) {
        return [];
    }
    
    $sanitized = [];
    
    foreach ($sections as $section) {
        if (!is_array($section) || !isset($section['type'])) {
            continue;
        }
        
        $clean_section = [
            'type' => sanitize_key($section['type']),
            'id' => isset($section['id']) ? sanitize_text_field($section['id']) : uniqid('section_')
        ];
        
        // Sanitize based on section type
        switch ($section['type']) {
            case 'hero':
                $clean_section['headline'] = sanitize_text_field($section['headline'] ?? '');
                $clean_section['subheadline'] = sanitize_textarea_field($section['subheadline'] ?? '');
                $clean_section['button_text'] = sanitize_text_field($section['button_text'] ?? '');
                $clean_section['button_url'] = esc_url_raw($section['button_url'] ?? '#');
                break;
                
            default:
                // For future section types, apply generic sanitization
                foreach ($section as $key => $value) {
                    if ($key !== 'type' && $key !== 'id') {
                        if (is_string($value)) {
                            $clean_section[$key] = sanitize_text_field($value);
                        } elseif (is_array($value)) {
                            $clean_section[$key] = array_map('sanitize_text_field', $value);
                        }
                    }
                }
                break;
        }
        
        $sanitized[] = $clean_section;
    }
    
    return $sanitized;
}

/**
 * Batch query optimization for multiple posts
 * 
 * @param array $post_ids Array of post IDs
 * @return array Associative array of post_id => sections
 */
function aisb_get_multiple_post_sections($post_ids) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return [];
    }
    
    global $wpdb;
    
    // Prepare placeholders for IN query
    $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    
    // Single query to get all sections at once
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_value 
         FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_sections' 
         AND post_id IN ($placeholders)",
        $post_ids
    ));
    
    $sections_data = [];
    
    foreach ($results as $row) {
        $sections = maybe_unserialize($row->meta_value);
        $sections_data[$row->post_id] = is_array($sections) ? $sections : [];
    }
    
    // Fill in empty arrays for posts without sections
    foreach ($post_ids as $post_id) {
        if (!isset($sections_data[$post_id])) {
            $sections_data[$post_id] = [];
        }
    }
    
    return $sections_data;
}

/**
 * Register REST API routes for link selection
 */
function aisb_register_rest_routes() {
    register_rest_route('aisb/v1', '/search-content', array(
        'methods' => 'GET',
        'callback' => 'aisb_search_content_callback',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'search' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'per_page' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 20,
            ),
        ),
    ));
}

/**
 * REST API callback for searching pages and posts
 */
function aisb_search_content_callback($request) {
    $search_term = $request->get_param('search');
    $per_page = $request->get_param('per_page');
    
    // Build query args
    $args = array(
        'post_type' => array('page', 'post'),
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'orderby' => 'relevance',
        'order' => 'DESC',
    );
    
    if (!empty($search_term)) {
        $args['s'] = $search_term;
    }
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_type_obj = get_post_type_object(get_post_type());
            
            $results[] = array(
                'id' => get_the_ID(),
                'text' => get_the_title() . ' (' . $post_type_obj->labels->singular_name . ')',
                'url' => get_permalink(),
                'type' => get_post_type(),
            );
        }
        wp_reset_postdata();
    }
    
    // Add option for custom URL at the beginning
    if (empty($search_term) || strpos(strtolower('custom url'), strtolower($search_term)) !== false) {
        array_unshift($results, array(
            'id' => 'custom',
            'text' => '— Enter Custom URL —',
            'url' => '',
            'type' => 'custom',
        ));
    }
    
    return rest_ensure_response(array('results' => $results));
}

/**
 * Clear cache when post is saved
 * 
 * @param int $post_id Post ID
 */
function aisb_clear_cache_on_save($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Clear section and enabled caches for this post
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
}

/**
 * Clear cache when post is deleted
 * 
 * @param int $post_id Post ID
 */
function aisb_clear_cache_on_delete($post_id) {
    wp_cache_delete('aisb_sections_' . $post_id, 'aisb');
    wp_cache_delete('aisb_enabled_' . $post_id, 'aisb');
}

/**
 * Get database performance statistics (for debugging/monitoring)
 * 
 * @return array Performance stats
 */
function aisb_get_performance_stats() {
    global $wpdb;
    
    // Get counts of posts using AISB
    $enabled_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_enabled' AND meta_value = '1'"
    );
    
    $sections_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
         WHERE meta_key = '_aisb_sections'"
    );
    
    // Check if indexes exist
    $indexes = $wpdb->get_results(
        "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name LIKE 'aisb_%'"
    );
    
    return [
        'enabled_posts' => (int) $enabled_count,
        'posts_with_sections' => (int) $sections_count,
        'database_indexes' => count($indexes),
        'cache_group' => 'aisb',
        'optimization_level' => 'advanced'
    ];
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'aisb_activate');

function aisb_activate() {
    // Add database indexes for performance
    aisb_add_database_indexes();
    
    // Set activation timestamp
    update_option('aisb_activated', current_time('timestamp'));
    
    // Future: Create database tables if needed for advanced features
}

/**
 * Encrypt API key for secure storage
 */
function aisb_encrypt_api_key($api_key) {
    if (empty($api_key)) {
        return '';
    }
    
    // Check if encryption constants are defined
    if (!defined('AISB_ENCRYPTION_KEY') || !defined('AISB_ENCRYPTION_SALT')) {
        // If not defined, store in plain text with warning (for development only)
        // In production, these constants should always be defined
        return base64_encode($api_key);
    }
    
    $method = 'aes-256-cbc';
    $key = substr(hash('sha256', AISB_ENCRYPTION_KEY), 0, 32);
    $iv = substr(hash('sha256', AISB_ENCRYPTION_SALT), 0, 16);
    
    return base64_encode(openssl_encrypt($api_key, $method, $key, 0, $iv));
}

/**
 * Decrypt API key for use
 */
function aisb_decrypt_api_key($encrypted_key) {
    if (empty($encrypted_key)) {
        return '';
    }
    
    // Check if encryption constants are defined
    if (!defined('AISB_ENCRYPTION_KEY') || !defined('AISB_ENCRYPTION_SALT')) {
        // If not defined, assume it was stored as base64 only
        return base64_decode($encrypted_key);
    }
    
    $method = 'aes-256-cbc';
    $key = substr(hash('sha256', AISB_ENCRYPTION_KEY), 0, 32);
    $iv = substr(hash('sha256', AISB_ENCRYPTION_SALT), 0, 16);
    
    return openssl_decrypt(base64_decode($encrypted_key), $method, $key, 0, $iv);
}

/**
 * AJAX handler for saving AI settings
 */
add_action('wp_ajax_aisb_save_ai_settings', 'aisb_ajax_save_ai_settings');

function aisb_ajax_save_ai_settings() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_ai_settings')) {
        wp_send_json_error(__('Security check failed.', 'ai-section-builder'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to save settings.', 'ai-section-builder'));
    }
    
    // Get and sanitize input
    $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    $keep_existing_key = isset($_POST['keep_existing_key']) ? intval($_POST['keep_existing_key']) : 0;
    
    // Get existing settings
    $settings = get_option('aisb_ai_settings', array());
    
    // Check if provider changed
    $provider_changed = isset($settings['provider']) && $settings['provider'] !== $provider;
    
    // Update settings
    $settings['provider'] = $provider;
    $settings['model'] = $model;
    
    // Handle API key updates
    if (!empty($api_key)) {
        // New API key provided
        $settings['api_key'] = aisb_encrypt_api_key($api_key);
        // Keep verified status since user had to test before saving
    } elseif ($keep_existing_key && !$provider_changed && isset($settings['api_key'])) {
        // Keep existing key (no change needed)
    } else if ($provider_changed) {
        // Provider changed, reset verification
        $settings['verified'] = false;
        $settings['last_verified'] = 0;
    }
    
    // Save settings
    update_option('aisb_ai_settings', $settings);
    
    // Prepare response
    $response_data = array(
        'message' => __('Settings saved successfully.', 'ai-section-builder'),
        'verified' => $settings['verified']
    );
    
    // Add masked key for display if key was updated
    if (!empty($api_key) && strlen($api_key) > 4) {
        $response_data['masked_key'] = str_repeat('•', strlen($api_key) - 4) . substr($api_key, -4);
    }
    
    wp_send_json_success($response_data);
}

/**
 * AJAX handler for testing AI connection
 */
add_action('wp_ajax_aisb_test_ai_connection', 'aisb_ajax_test_ai_connection');

function aisb_ajax_test_ai_connection() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aisb_ai_settings')) {
        wp_send_json_error(__('Security check failed.', 'ai-section-builder'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to test connection.', 'ai-section-builder'));
    }
    
    // Get input
    $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    
    if (empty($provider) || empty($api_key)) {
        wp_send_json_error(__('Provider and API key are required.', 'ai-section-builder'));
    }
    
    // Test the connection based on provider
    $test_result = false;
    $error_message = '';
    
    if ($provider === 'openai') {
        // Test OpenAI connection
        $test_result = aisb_test_openai_connection($api_key, $model);
    } elseif ($provider === 'anthropic') {
        // Test Anthropic connection
        $test_result = aisb_test_anthropic_connection($api_key, $model);
    } else {
        wp_send_json_error(__('Invalid provider selected.', 'ai-section-builder'));
    }
    
    if ($test_result['success']) {
        // Update verification status in database
        $settings = get_option('aisb_ai_settings', array());
        $settings['verified'] = true;
        $settings['last_verified'] = current_time('timestamp');
        update_option('aisb_ai_settings', $settings);
        
        wp_send_json_success(array(
            'message' => $test_result['message']
        ));
    } else {
        wp_send_json_error($test_result['message']);
    }
}

/**
 * Test OpenAI API connection
 */
function aisb_test_openai_connection($api_key, $model = '') {
    $url = 'https://api.openai.com/v1/models';
    
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'timeout' => 10
    ));
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => sprintf(__('Connection error: %s', 'ai-section-builder'), $response->get_error_message())
        );
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 200) {
        return array(
            'success' => true,
            'message' => __('Successfully connected to OpenAI API.', 'ai-section-builder')
        );
    } elseif ($status_code === 401) {
        return array(
            'success' => false,
            'message' => __('Invalid API key. Please check your OpenAI API key.', 'ai-section-builder')
        );
    } else {
        return array(
            'success' => false,
            'message' => sprintf(__('API returned error code: %d', 'ai-section-builder'), $status_code)
        );
    }
}

/**
 * Test Anthropic API connection
 */
function aisb_test_anthropic_connection($api_key, $model = '') {
    // Use a simple API call to test the connection
    $url = 'https://api.anthropic.com/v1/messages';
    
    // Create a minimal test message
    $body = array(
        'model' => $model ?: 'claude-3-haiku-20240307',
        'max_tokens' => 10,
        'messages' => array(
            array(
                'role' => 'user',
                'content' => 'Hi'
            )
        )
    );
    
    $response = wp_remote_post($url, array(
        'headers' => array(
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($body),
        'timeout' => 10
    ));
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => sprintf(__('Connection error: %s', 'ai-section-builder'), $response->get_error_message())
        );
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 200) {
        return array(
            'success' => true,
            'message' => __('Successfully connected to Anthropic API.', 'ai-section-builder')
        );
    } elseif ($status_code === 401) {
        return array(
            'success' => false,
            'message' => __('Invalid API key. Please check your Anthropic API key.', 'ai-section-builder')
        );
    } elseif ($status_code === 400) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('Invalid request.', 'ai-section-builder');
        return array(
            'success' => false,
            'message' => sprintf(__('API Error: %s', 'ai-section-builder'), $error_message)
        );
    } else {
        return array(
            'success' => false,
            'message' => sprintf(__('API returned error code: %d', 'ai-section-builder'), $status_code)
        );
    }
}

/**
 * Deactivation hook  
 */
register_deactivation_hook(__FILE__, 'aisb_deactivate');

function aisb_deactivate() {
    // Clean up if needed
}
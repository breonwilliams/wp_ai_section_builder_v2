/**
 * AI Section Builder - Visual Editor JavaScript
 * Handles editor interactions and section management
 * 
 * @package AISB
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    // Editor state
    var editorState = {
        sections: [],
        currentSection: null,
        isDirty: false,
        reorderMode: {
            active: false,
            selectedIndex: null,
            targetIndex: null
        },
        lastSaved: null,
        sortableInstance: null,
        debug: false // Debug mode - set to true to troubleshoot
    };
    
    // Debug helper
    function debugLog(context, data) {
        if (editorState.debug) {
            console.log(`[AISB DEBUG - ${context}]:`, data);
        }
    }
    
    /**
     * Initialize editor
     */
    function initEditor() {
        // Initialize drag-drop capabilities (Phase 2)
        initDragDropCapabilities();
        
        // Load existing sections if any
        loadSections();
        
        // Bind events
        bindEvents();
        
        // Initialize sidebar toggle
        initSidebarToggle();
    }
    
    /**
     * Initialize drag-drop capabilities
     */
    function initDragDropCapabilities() {
        // Check if drag-drop should be enabled
        var features = (window.aisbEditor && window.aisbEditor.features) || {};
        
        if (features.dragDrop) {
            // Check if Sortable.js is available
            if (typeof Sortable === 'undefined') {
                console.warn('AISB: Sortable.js failed to load, falling back to keyboard-only mode');
                showNotification('Drag-drop unavailable. Use Enter key on drag handles for keyboard reordering.', 'warning');
                return;
            }
            
            // Initialize Sortable.js with error boundary
            try {
                initSortableJS();
                console.log('AISB: Sortable.js drag-drop initialized successfully');
            } catch (error) {
                console.error('AISB: Sortable.js initialization failed:', error);
                showNotification('Drag-drop initialization failed. Keyboard navigation available.', 'error');
            }
        } else {
            console.log('AISB: Keyboard navigation mode (drag-drop disabled)');
        }
    }
    
    /**
     * Initialize Sortable.js functionality
     */
    function initSortableJS() {
        // Wait for DOM to be ready and section list to exist
        function setupSortable() {
            var sectionList = document.getElementById('aisb-section-list');
            if (!sectionList) {
                console.warn('AISB: Section list not found, retrying...');
                setTimeout(setupSortable, 100);
                return;
            }
            
            // Initialize Sortable.js
            var sortable = new Sortable(sectionList, {
                // Basic Configuration
                animation: 200, // Smooth animations
                easing: 'cubic-bezier(0.4, 0.0, 0.2, 1)', // Material Design easing
                delay: 0, // No delay on desktop
                delayOnTouchStart: true, // Delay on touch to prevent conflicts with scrolling
                touchStartThreshold: 5, // px, how many pixels the point should move before cancelling
                
                // Drag Handle
                handle: '.aisb-section-item__drag', // Only drag via handle
                draggable: '.aisb-section-item', // Only drag section items
                draggable: '.aisb-section-item', // What elements are draggable
                
                // Visual Feedback
                ghostClass: 'aisb-sortable-ghost', // Class for the drop placeholder
                chosenClass: 'aisb-sortable-chosen', // Class for the chosen item
                dragClass: 'aisb-sortable-drag', // Class for the dragging item
                
                // Behavior
                forceFallback: false, // Use native HTML5 drag-drop when possible
                fallbackClass: 'aisb-sortable-fallback', // Class for fallback mode
                fallbackOnBody: true, // Append ghost to body for better positioning
                
                // Disable text selection during drag
                preventOnFilter: false,
                filter: '.aisb-section-item__actions', // Don't start drag on action buttons
                
                // Event Handlers
                onStart: function(evt) {
                    handleDragStart(evt);
                },
                
                onMove: function(evt) {
                    return handleDragMove(evt);
                },
                
                onEnd: function(evt) {
                    handleDragEnd(evt);
                }
            });
            
            // Store sortable instance for cleanup
            editorState.sortableInstance = sortable;
            
            // Debug: Log sortable configuration
            console.log('AISB: Sortable initialized with options:', {
                container: sectionList.id,
                childCount: sectionList.children.length,
                draggableSelector: '.aisb-section-item'
            });
        }
        
        setupSortable();
    }
    
    /**
     * Handle drag start event
     */
    function handleDragStart(evt) {
        var draggedIndex = parseInt(evt.oldIndex);
        
        // Store drag state
        editorState.reorderMode.active = true;
        editorState.reorderMode.selectedIndex = draggedIndex;
        
        // Add visual feedback
        document.body.classList.add('aisb-dragging');
        
        // Announce to screen readers
        announceToScreenReader('Dragging section. Move to desired position and release.');
        
        console.log('AISB: Drag started for section', draggedIndex);
    }
    
    /**
     * Handle drag move event (validation)
     */
    function handleDragMove(evt) {
        // Allow all moves by default
        // Future: Add logic to prevent invalid drops
        return true;
    }
    
    /**
     * Handle drag end event
     */
    function handleDragEnd(evt) {
        var oldIndex = parseInt(evt.oldIndex);
        var newIndex = parseInt(evt.newIndex);
        
        // Clean up visual state
        document.body.classList.remove('aisb-dragging');
        editorState.reorderMode.active = false;
        editorState.reorderMode.selectedIndex = null;
        
        // Validate indices
        if (isNaN(oldIndex) || isNaN(newIndex)) {
            console.error('AISB: Invalid drag indices', oldIndex, newIndex);
            return;
        }
        
        if (oldIndex < 0 || oldIndex >= editorState.sections.length || 
            newIndex < 0 || newIndex >= editorState.sections.length) {
            console.error('AISB: Drag indices out of bounds', oldIndex, newIndex, 'total sections:', editorState.sections.length);
            return;
        }
        
        // Only process if position actually changed
        if (oldIndex !== newIndex) {
            // Update sections array
            var section = editorState.sections.splice(oldIndex, 1)[0];
            if (!section) {
                console.error('AISB: No section found at index', oldIndex);
                return;
            }
            editorState.sections.splice(newIndex, 0, section);
            
            // Mark as dirty to show unsaved changes
            editorState.isDirty = true;
            
            // Update canvas to reflect new order (but avoid rebuilding section list)
            renderCanvasOnly();
            
            // Update section list with new order
            updateSectionListOrder();
            
            // Update save button to indicate unsaved changes
            updateSaveStatus('unsaved');
            
            // Announce success
            var successMsg = (window.aisbEditor && window.aisbEditor.i18n.sectionMoved) || 'Section moved successfully';
            announceToScreenReader(successMsg);
            
            console.log('AISB: Section moved from', oldIndex, 'to', newIndex);
        } else {
            console.log('AISB: Drag cancelled, no position change');
        }
    }
    
    /**
     * Reinitialize Sortable.js after DOM changes
     */
    function reinitializeSortable() {
        // Clean up existing instance safely
        if (editorState.sortableInstance) {
            try {
                // Check if the instance and its element still exist
                if (editorState.sortableInstance.el && editorState.sortableInstance.destroy) {
                    editorState.sortableInstance.destroy();
                }
            } catch (error) {
                // Silent fail - instance already destroyed or invalid
            }
            editorState.sortableInstance = null;
        }
        
        // Reinitialize if drag-drop is enabled
        var features = (window.aisbEditor && window.aisbEditor.features) || {};
        var sortableContainer = document.querySelector('.aisb-sections-list');
        if (features.dragDrop && typeof Sortable !== 'undefined' && sortableContainer) {
            setTimeout(initSortableJS, 10); // Small delay for DOM updates
        }
    }
    
    /**
     * Load existing sections
     */
    function loadSections() {
        // TODO: Load from hidden field or AJAX
        var existingSections = $('#aisb-existing-sections').val();
        if (existingSections) {
            try {
                editorState.sections = JSON.parse(existingSections);
                renderSections();
            } catch(e) {
                console.error('Error parsing sections:', e);
            }
        }
    }
    
    /**
     * Bind editor events
     */
    function bindEvents() {
        // Add section button - auto-add with defaults
        $('.aisb-section-type').on('click', function() {
            var sectionType = $(this).data('type');
            
            // Auto-add section with defaults
            var section = {
                type: sectionType,
                content: Object.assign({}, heroDefaults)
            };
            
            // Add to state and render immediately
            editorState.sections.push(section);
            editorState.isDirty = true;
            renderSections();
            
            // Update save button to show unsaved changes
            updateSaveStatus('unsaved');
            
            // Then open form for editing with the index
            var sectionIndex = editorState.sections.length - 1;
            openSectionForm(sectionType, sectionIndex);
        });
        
        // Edit section on click
        $(document).on('click', '.aisb-section', function() {
            var index = $(this).data('index');
            var section = editorState.sections[index];
            if (section) {
                openSectionForm(section.type, index);
            }
        });
        
        // Close settings panel
        $('.aisb-editor-close-panel').on('click', function() {
            closeSectionForm();
        });
        
        // Save button
        $('#aisb-save-sections').on('click', function() {
            saveSections();
        });
        
        // Responsive preview toggles
        $('.aisb-preview-toggle').on('click', function() {
            var view = $(this).data('view');
            setPreviewMode(view);
        });
        
        // Back to library button
        $('#aisb-back-to-library').on('click', function() {
            showLibraryMode();
        });
        
        // Section list item clicks
        $(document).on('click', '.aisb-section-item', function() {
            var index = $(this).data('index');
            var section = editorState.sections[index];
            if (section) {
                openSectionForm(section.type, index);
            }
        });
        
        // Section delete buttons
        $(document).on('click', '.aisb-section-item__action.delete', function(e) {
            e.stopPropagation();
            var index = $(this).closest('.aisb-section-item').data('index');
            deleteSection(index);
        });
        
        // Keyboard navigation for section list
        $(document).on('keydown', '.aisb-section-item', function(e) {
            handleSectionKeyNavigation(e, $(this));
        });
        
        // Drag handle keyboard activation
        $(document).on('keydown', '.aisb-section-item__drag', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.stopPropagation();
                var index = $(this).closest('.aisb-section-item').data('index');
                toggleReorderMode(index);
            }
        });
    }
    
    /**
     * Open section form in left panel
     */
    function openSectionForm(sectionType, sectionIndex) {
        // Store current editing index
        editorState.currentSection = sectionIndex;
        
        // Get existing content if editing
        var sectionContent = null;
        if (typeof sectionIndex !== 'undefined' && editorState.sections[sectionIndex]) {
            sectionContent = editorState.sections[sectionIndex].content;
        }
        
        // Generate form based on section type
        var formHtml = '';
        
        if (sectionType === 'hero') {
            formHtml = generateHeroForm(sectionContent);
        }
        
        // Switch to edit mode in left panel
        showEditMode(formHtml);
        
        // Update section list to show active state
        updateSectionList();
        
        // Bind form events
        bindFormEvents();
        
        // Initialize autocomplete on URL fields after form is rendered
        setTimeout(function() {
            initializeUrlAutocomplete();
        }, 300);
        
        // Initialize WYSIWYG editors with small delay for DOM
        setTimeout(function() {
            initWysiwygEditors();
        }, 100);
        
        // Initialize global blocks repeater if it's a hero section
        if (sectionType === 'hero') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || heroDefaults;
            // Migrate old structure if needed
            content = migrateOldFieldNames(content);
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initGlobalBlocksRepeater(content);
            }, 50);
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Strip HTML tags for textarea display
     */
    function stripHtmlTags(html) {
        if (!html) return '';
        // Remove <p> tags but keep content
        return html.replace(/<p>/g, '').replace(/<\/p>/g, '\n').replace(/<[^>]*>/g, '').trim();
    }
    
    /**
     * Wrap plain text in paragraph tags
     */
    function wrapInParagraphs(text) {
        if (!text) return '';
        // Split by newlines and wrap each in <p> tags
        return text.split('\n').filter(line => line.trim()).map(line => `<p>${escapeHtml(line)}</p>`).join('\n');
    }
    
    /**
     * Migrate old field names to new standardized structure
     */
    function migrateOldFieldNames(content) {
        if (!content) return heroDefaults;
        
        // Start with defaults and merge content on top to preserve all fields
        var migrated = $.extend({}, heroDefaults, content);
        
        // Migrate field names
        if ('eyebrow' in content && !('eyebrow_heading' in content)) {
            migrated.eyebrow_heading = content.eyebrow;
            delete migrated.eyebrow;
        }
        if ('headline' in content && !('heading' in content)) {
            migrated.heading = content.headline;
            delete migrated.headline;
        }
        if ('subheadline' in content && !('content' in content)) {
            // Wrap in paragraph tags if not already
            var text = content.subheadline;
            migrated.content = text.includes('<p>') ? text : `<p>${text}</p>`;
            delete migrated.subheadline;
        }
        
        // Migrate buttons to global_blocks
        if (content.buttons && !content.global_blocks) {
            migrated.global_blocks = content.buttons.map(function(btn) {
                return $.extend({ type: 'button' }, btn);
            });
            delete migrated.buttons;
        }
        
        // Migrate old single button fields
        if (content.button_text && !migrated.global_blocks) {
            migrated.global_blocks = [{
                type: 'button',
                id: 'btn_migrated_1',
                text: content.button_text,
                url: content.button_url || '#',
                style: 'primary'
            }];
        }
        
        // Migrate media fields to featured_image
        if (content.media_type === 'image' && content.media_image_url) {
            migrated.featured_image = content.media_image_url;
        }
        
        // Add default variants if not present
        if (!migrated.theme_variant) {
            migrated.theme_variant = 'dark';
        }
        if (!migrated.layout_variant) {
            migrated.layout_variant = 'content-left';
        }
        
        // Clean up ONLY truly obsolete fields
        // DO NOT delete media_type or video_url - we use those!
        delete migrated.media_image_id;
        delete migrated.media_image_url;
        delete migrated.media_image_alt;
        delete migrated.media_video_type;
        delete migrated.button_text;
        delete migrated.button_url;
        
        return migrated;
    }
    
    /**
     * Hero section field defaults - Standardized structure
     */
    var heroDefaults = {
        // Standard content fields
        eyebrow_heading: 'Welcome to the Future',
        heading: 'Your Headline Here',
        content: '<p>Add your compelling message that engages visitors</p>',
        outro_content: '',
        
        // Media fields
        media_type: 'none',
        featured_image: '',
        video_url: '',
        
        // Global blocks for nested components
        global_blocks: [
            {
                type: 'button',
                id: 'btn_default_1',
                text: 'Get Started',
                url: '#',
                style: 'primary'
            },
            {
                type: 'button',
                id: 'btn_default_2',
                text: 'Learn More',
                url: '#about',
                style: 'secondary'
            }
        ],
        
        // Variant fields
        theme_variant: 'dark',  // 'light' | 'dark'
        layout_variant: 'content-left',  // 'content-left' | 'content-right' | 'center'
        
        // CTA fields (for future use)
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: ''
    };
    
    /**
     * Generate Hero section form - Standardized fields
     */
    function generateHeroForm(content) {
        // Use existing content or defaults
        content = content || heroDefaults;
        
        // Migrate old field names if present
        content = migrateOldFieldNames(content);
        
        return `
            <form id="aisb-section-form">
                <!-- Variant Controls -->
                <div class="aisb-editor-form-group aisb-variant-controls">
                    <div class="aisb-variant-group">
                        <label class="aisb-editor-form-label">Theme</label>
                        <div class="aisb-toggle-group">
                            <button type="button" class="aisb-toggle-btn ${content.theme_variant === 'light' ? 'active' : ''}" 
                                    data-variant-type="theme" data-variant-value="light">
                                <span class="dashicons dashicons-sun"></span> Light
                            </button>
                            <button type="button" class="aisb-toggle-btn ${content.theme_variant === 'dark' ? 'active' : ''}" 
                                    data-variant-type="theme" data-variant-value="dark">
                                <span class="dashicons dashicons-moon"></span> Dark
                            </button>
                        </div>
                    </div>
                    
                    <div class="aisb-variant-group">
                        <label class="aisb-editor-form-label">Layout</label>
                        <div class="aisb-toggle-group">
                            <button type="button" class="aisb-toggle-btn ${content.layout_variant === 'content-left' ? 'active' : ''}" 
                                    data-variant-type="layout" data-variant-value="content-left">
                                <span class="dashicons dashicons-align-left"></span> Left
                            </button>
                            <button type="button" class="aisb-toggle-btn ${content.layout_variant === 'center' ? 'active' : ''}" 
                                    data-variant-type="layout" data-variant-value="center">
                                <span class="dashicons dashicons-align-center"></span> Center
                            </button>
                            <button type="button" class="aisb-toggle-btn ${content.layout_variant === 'content-right' ? 'active' : ''}" 
                                    data-variant-type="layout" data-variant-value="content-right">
                                <span class="dashicons dashicons-align-right"></span> Right
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content Fields -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-eyebrow-heading">
                        Eyebrow Heading
                    </label>
                    <input type="text" 
                           id="hero-eyebrow-heading" 
                           name="eyebrow_heading" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.eyebrow_heading || '')}" 
                           placeholder="Welcome to the Future">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-heading">
                        Heading
                    </label>
                    <input type="text" 
                           id="hero-heading" 
                           name="heading" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.heading || '')}" 
                           placeholder="Enter your main heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-content">
                        Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="hero-content" 
                                  name="content" 
                                  class="aisb-editor-wysiwyg">${content.content || ''}</textarea>
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Featured Image
                    </label>
                    ${generateMediaField(content)}
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Global Blocks
                    </label>
                    <div id="hero-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="hero-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Generate media field with support for images and videos
     */
    function generateMediaField(content) {
        var mediaType = content.media_type || 'none';
        var imageUrl = content.featured_image || '';
        var videoUrl = content.video_url || '';
        
        debugLog('generateMediaField', {
            mediaType: mediaType,
            imageUrl: imageUrl,
            videoUrl: videoUrl,
            fullContent: content
        });
        
        return `
            <div class="aisb-media-selector">
                <!-- Media Type Selector -->
                <div class="aisb-media-type-selector">
                    <label class="aisb-radio-label">
                        <input type="radio" name="media_type" value="none" ${mediaType === 'none' ? 'checked' : ''}>
                        <span>None</span>
                    </label>
                    <label class="aisb-radio-label">
                        <input type="radio" name="media_type" value="image" ${mediaType === 'image' ? 'checked' : ''}>
                        <span>Image</span>
                    </label>
                    <label class="aisb-radio-label">
                        <input type="radio" name="media_type" value="video" ${mediaType === 'video' ? 'checked' : ''}>
                        <span>Video</span>
                    </label>
                </div>
                
                <!-- Image Selection (shown when media_type is 'image') -->
                <div class="aisb-media-image-controls" style="${mediaType === 'image' ? '' : 'display:none'}">
                    ${imageUrl ? `
                        <div class="aisb-media-preview">
                            <img src="${escapeHtml(imageUrl)}" alt="Featured image">
                            <button type="button" class="aisb-media-remove" data-media-action="remove-image">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    ` : ''}
                    <button type="button" class="aisb-editor-btn aisb-editor-btn-ghost aisb-editor-btn-with-icon" id="select-featured-image">
                        <span class="dashicons dashicons-format-image"></span>
                        <span>${imageUrl ? 'Change Image' : 'Select Image'}</span>
                    </button>
                    <input type="hidden" name="featured_image" value="${escapeHtml(imageUrl)}">
                </div>
                
                <!-- Video URL Input (shown when media_type is 'video') -->
                <div class="aisb-media-video-controls" style="${mediaType === 'video' ? '' : 'display:none'}">
                    <input type="url" 
                           name="video_url" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(videoUrl)}"
                           placeholder="Enter YouTube URL or video file URL">
                    <p class="aisb-editor-help-text">
                        Supports YouTube URLs (e.g., https://youtube.com/watch?v=...) or direct video file URLs
                    </p>
                </div>
            </div>
        `;
    }
    
    
    /**
     * Initialize global blocks repeater (universal component system)
     */
    function initGlobalBlocksRepeater(content) {
        // Ensure container exists before initialization
        var $container = $('#hero-global-blocks');
        if (!$container.length) {
            console.error('AISB: Global blocks container not found');
            return;
        }
        
        // Get global blocks (already migrated from buttons)
        var globalBlocks = content.global_blocks || [];
        
        // Initialize repeater field
        var globalBlocksRepeater = $container.aisbRepeaterField({
            fieldName: 'global_blocks',
            items: globalBlocks,
            defaultItem: {
                type: 'button',
                text: 'Button Text',
                url: '#',
                target: '_self',
                style: 'primary'
            },
            maxItems: 10,
            minItems: 0,
            itemLabel: 'Component',
            addButtonText: 'Add Component',
            template: function(item, index) {
                // For now, only support buttons, but structure allows for expansion
                if (item.type === 'button') {
                    return globalBlockButtonTemplate(item, index);
                }
                // Future: Add support for cards, lists, features, etc.
                return '<div>Unknown component type</div>';
            },
            onUpdate: function(items) {
                // Update the section content with global blocks data
                if (editorState.currentSection !== null && editorState.sections[editorState.currentSection]) {
                    // Preserve existing content and update global blocks
                    editorState.sections[editorState.currentSection].content.global_blocks = items;
                    editorState.isDirty = true;
                    
                    // Re-render the preview section
                    var section = editorState.sections[editorState.currentSection];
                    var sectionHtml = renderSection(section, editorState.currentSection);
                    $('.aisb-section[data-index="' + editorState.currentSection + '"]').replaceWith(sectionHtml);
                    
                    // Update save status
                    updateSaveStatus('unsaved');
                }
                
                // Re-initialize autocomplete on URL fields after render
                setTimeout(function() {
                    initializeUrlAutocomplete();
                }, 150);
            }
        });
        
        // Initialize autocomplete on any existing URL fields after repeater renders
        setTimeout(function() {
            initializeUrlAutocomplete();
        }, 250);
        
        return globalBlocksRepeater;
    }
    
    /**
     * Global block button template (component template)
     */
    function globalBlockButtonTemplate(button, index) {
        var styles = [
            { value: 'primary', label: 'Primary' },
            { value: 'secondary', label: 'Secondary' }
        ];
        
        var styleOptions = styles.map(function(style) {
            return '<option value="' + style.value + '"' + 
                   (button.style === style.value ? ' selected' : '') + '>' + 
                   style.label + '</option>';
        }).join('');
        
        return `
            <div class="aisb-repeater-fields">
                <div class="aisb-repeater-field-group">
                    <label>Button Text</label>
                    <input type="text" 
                           class="aisb-editor-input aisb-repeater-field" 
                           data-field="text" 
                           value="${escapeHtml(button.text || '')}" 
                           placeholder="Button text">
                </div>
                <div class="aisb-repeater-field-group">
                    <label>Button URL</label>
                    <input type="text" 
                           class="aisb-editor-input aisb-repeater-field aisb-url-autocomplete" 
                           data-field="url" 
                           value="${escapeHtml(button.url || '')}" 
                           placeholder="Start typing page name or enter URL">
                    <label class="aisb-checkbox-label">
                        <input type="checkbox" 
                               class="aisb-repeater-field" 
                               data-field="target" 
                               value="_blank"
                               ${button.target === '_blank' ? 'checked' : ''}>
                        Open in new tab
                    </label>
                </div>
                <div class="aisb-repeater-field-group">
                    <label>Button Style</label>
                    <select class="aisb-editor-input aisb-repeater-field" 
                            data-field="style">
                        ${styleOptions}
                    </select>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize WYSIWYG editors using WordPress TinyMCE
     */
    function initWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('hero-content');
            wp.editor.remove('hero-outro-content');
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('hero-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    paste_as_text: false,
                    paste_remove_styles: true,
                    paste_remove_styles_if_webkit: true,
                    paste_strip_class_attributes: 'all',
                    height: 200,
                    setup: function(editor) {
                        editor.on('change keyup', function() {
                            editor.save(); // Save to textarea
                            updatePreview();
                        });
                    }
                },
                quicktags: {
                    buttons: 'strong,em,link,ul,ol,li'
                },
                mediaButtons: false // No media button, we have our own
            });
            
            // Outro content editor (simpler)
            wp.editor.initialize('hero-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'bold,italic,link,unlink',
                    toolbar2: '',
                    paste_as_text: false,
                    paste_remove_styles: true,
                    height: 150,
                    setup: function(editor) {
                        editor.on('change keyup', function() {
                            editor.save(); // Save to textarea
                            updatePreview();
                        });
                    }
                },
                quicktags: {
                    buttons: 'strong,em,link'
                },
                mediaButtons: false
            });
        } else {
            console.warn('WordPress editor not available, falling back to textarea');
        }
    }
    
    /**
     * Bind form events
     */
    function bindFormEvents() {
        // Live preview on input - use delegate for dynamic elements
        $(document).on('input', '#aisb-section-form input, #aisb-section-form textarea', function() {
            updatePreview();
        });
        
        // Variant toggle buttons
        $(document).on('click', '.aisb-toggle-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var variantType = $btn.data('variant-type');
            var variantValue = $btn.data('variant-value');
            
            // Update active state
            $btn.siblings().removeClass('active');
            $btn.addClass('active');
            
            // Update section content
            if (editorState.currentSection !== null) {
                var content = editorState.sections[editorState.currentSection].content;
                if (variantType === 'theme') {
                    content.theme_variant = variantValue;
                } else if (variantType === 'layout') {
                    content.layout_variant = variantValue;
                }
                updatePreview();
            }
        });
        
        // Initialize media handlers
        initMediaHandlers();
    }
    
    /**
     * Initialize media selection handlers
     */
    function initMediaHandlers() {
        var mediaFrame;
        
        // Featured image selection
        $(document).on('click', '#select-featured-image', function(e) {
            e.preventDefault();
            
            // If the media frame already exists, reopen it
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }
            
            // Create the media frame
            mediaFrame = wp.media({
                title: 'Select Hero Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected, run a callback
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                
                debugLog('Image Selected from Media Library', {
                    url: attachment.url,
                    currentSection: editorState.currentSection
                });
                
                // Update the section content
                if (editorState.currentSection !== null) {
                    var content = editorState.sections[editorState.currentSection].content;
                    content.featured_image = attachment.url;
                    content.media_type = 'image'; // Ensure media type is set
                    
                    debugLog('Image URL Set in State', {
                        sectionIndex: editorState.currentSection,
                        updatedContent: content
                    });
                    
                    // Re-render the media field
                    $('.aisb-media-selector').replaceWith(generateMediaField(content));
                    updatePreview();
                    
                    // Force re-render of sections to ensure immediate update
                    renderSections();
                }
            });
            
            // Fix aria-hidden focus warning when modal opens
            mediaFrame.on('open', function() {
                // Remove aria-hidden from modal elements to prevent focus warning
                setTimeout(function() {
                    $('.media-modal').removeAttr('aria-hidden');
                    $('.media-modal-backdrop').removeAttr('aria-hidden');
                }, 100);
            });
            
            // Finally, open the modal
            mediaFrame.open();
        });
        
        // Remove media actions
        $(document).on('click', '[data-media-action]', function(e) {
            e.preventDefault();
            var action = $(this).data('media-action');
            
            if (editorState.currentSection !== null) {
                var content = editorState.sections[editorState.currentSection].content;
                
                if (action === 'remove-image') {
                    content.featured_image = '';
                    $('.aisb-media-selector').replaceWith(generateMediaField(content));
                }
                
                updatePreview();
            }
        });
        
        // Handle media type switching
        $(document).on('change', 'input[name="media_type"]', function() {
            var mediaType = $(this).val();
            var $selector = $(this).closest('.aisb-media-selector');
            
            debugLog('Media Type Changed', {
                newType: mediaType,
                currentSection: editorState.currentSection
            });
            
            // Hide all media controls
            $selector.find('.aisb-media-image-controls, .aisb-media-video-controls').hide();
            
            // Show the selected control
            if (mediaType === 'image') {
                $selector.find('.aisb-media-image-controls').show();
            } else if (mediaType === 'video') {
                $selector.find('.aisb-media-video-controls').show();
            }
            
            // Update the content immediately and persist
            if (editorState.currentSection !== null) {
                var content = editorState.sections[editorState.currentSection].content;
                content.media_type = mediaType;
                
                // PRESERVE all media data regardless of type selection
                // "None" is a display preference, not data destruction
                // This follows modern UX patterns where "hide" != "delete"
                // Explicit removal actions (remove buttons) handle actual data clearing
                
                debugLog('Media Type Updated in State', {
                    sectionIndex: editorState.currentSection,
                    updatedContent: content,
                    preservedData: {
                        featured_image: content.featured_image,
                        video_url: content.video_url
                    }
                });
                
                updatePreview();
            }
        });
        
        // Handle video URL input changes
        $(document).on('input', 'input[name="video_url"]', function() {
            var videoUrl = $(this).val();
            
            debugLog('Video URL Changed', {
                url: videoUrl,
                currentSection: editorState.currentSection
            });
            
            if (editorState.currentSection !== null) {
                var content = editorState.sections[editorState.currentSection].content;
                content.video_url = videoUrl;
                
                debugLog('Video URL Updated in State', {
                    sectionIndex: editorState.currentSection,
                    updatedContent: content
                });
                
                updatePreview();
            }
        });
    }
    
    /**
     * Initialize sidebar toggle functionality
     */
    function initSidebarToggle() {
        var $toggleBtn = $('#aisb-toggle-sidebars');
        var $layout = $('.aisb-editor-layout');
        var $leftPanel = $('.aisb-editor-panel--left');
        var $rightPanel = $('.aisb-editor-panel--right');
        var sidebarsVisible = true;
        
        // Load saved state from sessionStorage
        var savedState = sessionStorage.getItem('aisb_sidebars_visible');
        if (savedState !== null) {
            sidebarsVisible = savedState === 'true';
            updateSidebarState(sidebarsVisible);
        }
        
        // Toggle button click handler
        $toggleBtn.on('click', function() {
            sidebarsVisible = !sidebarsVisible;
            updateSidebarState(sidebarsVisible);
            sessionStorage.setItem('aisb_sidebars_visible', sidebarsVisible);
        });
        
        // Keyboard shortcut (Shift + S) - Only when not typing in input fields
        $(document).on('keydown', function(e) {
            // Check if user is typing in an input field
            var tagName = e.target.tagName.toLowerCase();
            var isTyping = tagName === 'input' || 
                          tagName === 'textarea' || 
                          tagName === 'select' ||
                          e.target.contentEditable === 'true';
            
            // Only trigger shortcut when NOT typing
            if (!isTyping && e.shiftKey && e.key === 'S') {
                e.preventDefault();
                $toggleBtn.trigger('click');
            }
        });
        
        // Update sidebar visibility state
        function updateSidebarState(visible) {
            if (visible) {
                $layout.removeClass('aisb-sidebars-hidden');
                $leftPanel.show();
                $rightPanel.show();
                $toggleBtn.addClass('active')
                    .attr('aria-pressed', 'true')
                    .find('.dashicons')
                    .removeClass('dashicons-editor-expand')
                    .addClass('dashicons-editor-contract');
                $toggleBtn.find('.aisb-btn-label').text('Hide Panels');
            } else {
                $layout.addClass('aisb-sidebars-hidden');
                $leftPanel.hide();
                $rightPanel.hide();
                $toggleBtn.removeClass('active')
                    .attr('aria-pressed', 'false')
                    .find('.dashicons')
                    .removeClass('dashicons-editor-contract')
                    .addClass('dashicons-editor-expand');
                $toggleBtn.find('.aisb-btn-label').text('Show Panels');
            }
            
            // Trigger resize event for canvas adjustment
            $(window).trigger('resize');
        }
    }
    
    /**
     * Add section to editor
     */
    function addSection() {
        var formData = $('#aisb-section-form').serializeArray();
        var section = {
            type: 'hero',
            content: {}
        };
        
        // Convert form data to object
        $.each(formData, function(i, field) {
            section.content[field.name] = field.value;
        });
        
        // Update existing section if editing
        if (editorState.currentSection !== null) {
            editorState.sections[editorState.currentSection] = section;
        } else {
            // Add new section
            editorState.sections.push(section);
        }
        
        editorState.isDirty = true;
        
        // Render sections
        renderSections();
        
        // Close form
        closeSectionForm();
    }
    
    /**
     * Show edit mode in left panel
     */
    function showEditMode(formHtml) {
        // Ensure sections panel is active
        $('.aisb-panel-tab').removeClass('active');
        $('#aisb-tab-sections').addClass('active');
        $('.aisb-panel-content').removeClass('active');
        $('#aisb-panel-sections').addClass('active');
        
        // Show edit mode
        $('#aisb-library-mode').hide();
        $('#aisb-edit-content').html(formHtml);
        $('#aisb-edit-mode').show();
    }
    
    /**
     * Show library mode in left panel
     */
    function showLibraryMode() {
        // Destroy WYSIWYG editors before hiding
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('hero-content');
            wp.editor.remove('hero-outro-content');
        }
        
        // Ensure sections panel is active
        $('.aisb-panel-tab').removeClass('active');
        $('#aisb-tab-sections').addClass('active');
        $('.aisb-panel-content').removeClass('active');
        $('#aisb-panel-sections').addClass('active');
        
        $('#aisb-edit-mode').hide();
        $('#aisb-edit-content').empty();
        $('#aisb-library-mode').show();
        editorState.currentSection = null;
        updateSectionList();
    }
    
    /**
     * Close section form (for backward compatibility)
     */
    function closeSectionForm() {
        showLibraryMode();
    }
    
    /**
     * Update live preview
     */
    function updatePreview() {
        if (editorState.currentSection === null) return;
        
        debugLog('updatePreview - Starting', {
            currentSection: editorState.currentSection
        });
        
        var formData = $('#aisb-section-form').serializeArray();
        var content = {};
        
        debugLog('updatePreview - Form Data', formData);
        
        // Convert form data to object
        $.each(formData, function(i, field) {
            // Content fields are now handled by TinyMCE - preserve HTML
            content[field.name] = field.value;
        });
        
        debugLog('updatePreview - Content from Form', content);
        
        // IMPORTANT: Preserve complex data that's managed outside the form
        var currentSection = editorState.sections[editorState.currentSection];
        if (currentSection && currentSection.content) {
            // Preserve global blocks managed by repeater
            if (currentSection.content.global_blocks) {
                content.global_blocks = currentSection.content.global_blocks;
            }
            
            // Preserve variant data managed by toggle buttons
            if (currentSection.content.theme_variant) {
                content.theme_variant = currentSection.content.theme_variant;
            }
            if (currentSection.content.layout_variant) {
                content.layout_variant = currentSection.content.layout_variant;
            }
            
            // ALWAYS preserve media fields - they are managed outside the form
            // The form doesn't serialize hidden inputs properly
            if (currentSection.content.media_type !== undefined) {
                content.media_type = currentSection.content.media_type;
            }
            if (currentSection.content.featured_image !== undefined) {
                content.featured_image = currentSection.content.featured_image;
            }
            if (currentSection.content.video_url !== undefined) {
                content.video_url = currentSection.content.video_url;
            }
        }
        
        debugLog('updatePreview - Final Content After Merge', content);
        
        // Update section in state
        if (editorState.sections[editorState.currentSection]) {
            editorState.sections[editorState.currentSection].content = content;
            editorState.isDirty = true;
            
            // Re-render just this section
            var section = editorState.sections[editorState.currentSection];
            var sectionHtml = renderSection(section, editorState.currentSection);
            $('.aisb-section[data-index="' + editorState.currentSection + '"]').replaceWith(sectionHtml);
            
            // Update section list to show new title (without reinitializing sortable)
            updateSectionList(true); // Pass flag to skip sortable reinit
            
            // Update save button to indicate unsaved changes
            updateSaveStatus('unsaved');
        }
    }
    
    /**
     * Render sections in canvas
     */
    function renderSections() {
        var $canvas = $('#aisb-sections-preview');
        var $emptyState = $('.aisb-editor-empty-state');
        
        if (editorState.sections.length === 0) {
            // Show empty state
            if ($emptyState.length) {
                $emptyState.show();
            }
            $canvas.hide();
        } else {
            // Hide empty state and show canvas
            if ($emptyState.length) {
                $emptyState.hide();
            }
            $canvas.show().empty();
            
            // Render each section
            $.each(editorState.sections, function(index, section) {
                var sectionHtml = renderSection(section, index);
                $canvas.append(sectionHtml);
            });
        }
        
        // Update section list in right panel
        updateSectionList();
    }
    
    /**
     * Render only the canvas (not section list) to avoid disrupting drag-drop
     */
    function renderCanvasOnly() {
        var $canvas = $('#aisb-sections-preview');
        var $emptyState = $('.aisb-editor-empty-state');
        
        if (editorState.sections.length === 0) {
            // Show empty state
            if ($emptyState.length) {
                $emptyState.show();
            }
            $canvas.hide();
        } else {
            // Hide empty state and show canvas
            if ($emptyState.length) {
                $emptyState.hide();
            }
            $canvas.show().empty();
            
            // Render each section
            $.each(editorState.sections, function(index, section) {
                var sectionHtml = renderSection(section, index);
                $canvas.append(sectionHtml);
            });
        }
    }
    
    /**
     * Update section list order without full rebuild (for drag operations)
     */
    function updateSectionListOrder() {
        // Simply update ARIA labels and data-index attributes without rebuilding
        $('.aisb-section-item').each(function(index) {
            var $item = $(this);
            var section = editorState.sections[index];
            if (section) {
                var title = section.content.heading || section.content.headline || 'Untitled Section';
                var type = section.type.charAt(0).toUpperCase() + section.type.slice(1);
                
                $item.attr('data-index', index);
                $item.attr('aria-label', type + ' section: ' + title + '. Position ' + (index + 1) + ' of ' + editorState.sections.length);
                
                // Update title in case it changed
                $item.find('.aisb-section-item__title').text(title);
            }
        });
    }
    
    /**
     * Render individual section
     */
    function renderSection(section, index) {
        if (!section) {
            console.error('AISB: renderSection called with undefined section at index', index);
            return '';
        }
        if (section.type === 'hero') {
            return renderHeroSection(section, index);
        }
        return '';
    }
    
    /**
     * Render media preview based on type and content
     */
    function renderMediaPreview(content) {
        var mediaType = content.media_type || 'none';
        var imageUrl = content.featured_image || '';
        var videoUrl = content.video_url || '';
        
        debugLog('renderMediaPreview Called', {
            mediaType: mediaType,
            imageUrl: imageUrl,
            videoUrl: videoUrl,
            fullContent: content
        });
        
        // No media
        if (mediaType === 'none') {
            debugLog('renderMediaPreview - No Media Type', 'Returning empty');
            return '';
        }
        
        // Image media
        if (mediaType === 'image') {
            if (imageUrl) {
                debugLog('renderMediaPreview - Rendering Image', imageUrl);
                return `
                    <div class="aisb-hero__media">
                        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(content.heading || '')}" />
                    </div>
                `;
            } else {
                debugLog('renderMediaPreview - Showing Image Placeholder', 'No image URL');
                // Show placeholder with SVG
                return `
                    <div class="aisb-hero__media">
                        <div class="aisb-media-placeholder aisb-media-placeholder--image">
                            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="8" y="12" width="48" height="40" rx="4" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4"/>
                                <circle cx="22" cy="26" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 40L22 26L32 36L40 28L56 44V48C56 50.2091 54.2091 52 52 52H12C9.79086 52 8 50.2091 8 48V40Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                            <span>Image Placeholder</span>
                        </div>
                    </div>
                `;
            }
        }
        
        // Video media
        if (mediaType === 'video') {
            if (videoUrl) {
                debugLog('renderMediaPreview - Processing Video', videoUrl);
                // Check if YouTube
                var youtubeMatch = videoUrl.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/);
                if (youtubeMatch && youtubeMatch[1]) {
                    debugLog('renderMediaPreview - YouTube Video Detected', youtubeMatch[1]);
                    return `
                        <div class="aisb-hero__media">
                            <iframe class="aisb-hero__video" 
                                    src="https://www.youtube-nocookie.com/embed/${youtubeMatch[1]}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    `;
                } else {
                    // Self-hosted video
                    return `
                        <div class="aisb-hero__media">
                            <video class="aisb-hero__video" controls>
                                <source src="${escapeHtml(videoUrl)}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    `;
                }
            } else {
                // Show placeholder with SVG
                return `
                    <div class="aisb-hero__media">
                        <div class="aisb-media-placeholder aisb-media-placeholder--video">
                            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="8" y="16" width="48" height="32" rx="4" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4"/>
                                <path d="M26 24V40L40 32L26 24Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <circle cx="48" cy="22" r="2" fill="currentColor"/>
                                <circle cx="48" cy="42" r="2" fill="currentColor"/>
                            </svg>
                            <span>Video Placeholder</span>
                        </div>
                    </div>
                `;
            }
        }
        
        return '';
    }
    
    /**
     * Render global blocks (buttons, cards, etc.)
     */
    function renderGlobalBlocks(blocks) {
        if (!blocks || !blocks.length) return '';
        
        var html = '';
        var buttons = blocks.filter(function(block) { return block.type === 'button'; });
        
        // Render buttons if any
        if (buttons.length) {
            var buttonHtml = buttons.map(function(button) {
                if (!button.text) return '';
                var styleClass = 'aisb-btn-' + (button.style || 'primary');
                
                // In preview, render as button tags (not links) to avoid styling issues
                return `<button class="aisb-btn ${styleClass}" type="button">${escapeHtml(button.text)}</button>`;
            }).join('');
            
            if (buttonHtml) {
                html += `<div class="aisb-hero__buttons">${buttonHtml}</div>`;
            }
        }
        
        // Future: Add rendering for other block types (cards, lists, etc.)
        
        return html;
    }
    
    
    /**
     * Render Hero section with standardized fields
     */
    function renderHeroSection(section, index) {
        var content = migrateOldFieldNames(section.content || section);
        
        // Build class list based on variants
        var sectionClasses = [
            'aisb-section',
            'aisb-section-hero',
            'aisb-section--' + (content.theme_variant || 'dark'),
            'aisb-section--' + (content.layout_variant || 'content-left')
        ].join(' ');
        
        return `
            <div class="${sectionClasses}" data-index="${index}">
                <section class="aisb-hero">
                    <div class="aisb-hero__container">
                        <div class="aisb-hero__grid">
                            <div class="aisb-hero__content">
                                ${content.eyebrow_heading ? `<div class="aisb-hero__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                                <h1 class="aisb-hero__heading">${escapeHtml(content.heading || 'Your Headline Here')}</h1>
                                <div class="aisb-hero__body">${content.content || '<p>Your compelling message goes here</p>'}</div>
                                ${renderGlobalBlocks(content.global_blocks)}
                                ${content.outro_content ? `<div class="aisb-hero__outro">${content.outro_content}</div>` : ''}
                            </div>
                            ${renderMediaPreview(content)}
                        </div>
                    </div>
                </section>
            </div>
        `;
    }
    
    /**
     * Update save status indicator
     */
    function updateSaveStatus(status) {
        var $saveBtn = $('#aisb-save-sections');
        var $statusIndicator = $('#aisb-save-status');
        
        // Create status indicator if it doesn't exist
        if (!$statusIndicator.length) {
            $statusIndicator = $('<span id="aisb-save-status" class="aisb-save-status"></span>');
            $saveBtn.after($statusIndicator);
        }
        
        // Reset classes
        $statusIndicator.removeClass('saving saved error unsaved');
        $saveBtn.removeClass('has-changes');
        
        // Ensure button is enabled for states that allow saving
        if (status !== 'saving') {
            $saveBtn.prop('disabled', false);
        }
        
        switch (status) {
            case 'unsaved':
                $saveBtn.addClass('has-changes');
                $statusIndicator.addClass('unsaved').text('Unsaved changes');
                break;
            case 'saving':
                $saveBtn.prop('disabled', true);
                $statusIndicator.addClass('saving').text('Saving...');
                break;
            case 'saved':
                $statusIndicator.addClass('saved').text('Saved');
                setTimeout(function() {
                    $statusIndicator.text('');
                }, 3000);
                break;
            case 'error':
                $statusIndicator.addClass('error').text('Save failed');
                // Keep button enabled so user can retry
                $saveBtn.prop('disabled', false);
                break;
            default:
                $statusIndicator.text('');
        }
    }
    
    /**
     * Save sections (manual save button)
     */
    function saveSections() {
        // Perform manual save
        updateSaveStatus('saving');
        
        saveSectionsToServer()
            .then(function() {
                editorState.isDirty = false;
                editorState.lastSaved = new Date();
                updateSaveStatus('saved');
                showNotification('Page saved successfully');
            })
            .catch(function(error) {
                updateSaveStatus('error');
                showNotification('Save failed: ' + error.message, 'error');
            });
    }
    
    /**
     * Save sections to server (core functionality)
     */
    function saveSectionsToServer() {
        var $button = $('#aisb-save-sections');
        var postId = $('#aisb-post-id').val();
        var nonce = $('#aisb_editor_nonce').val();
        
        // Disable button and store original text
        var originalText = $button.text();
        $button.prop('disabled', true).text('Saving...');
        
        // Return a Promise for consistent error handling
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_save_sections',
                    post_id: postId,
                    sections: JSON.stringify(editorState.sections),
                    nonce: nonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    // Always re-enable button
                    $button.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.data || 'Unknown server error'));
                    }
                },
                error: function(xhr, status, error) {
                    // Always re-enable button on error
                    $button.prop('disabled', false).text(originalText);
                    
                    var message = 'Network error';
                    if (status === 'timeout') {
                        message = 'Request timed out. Please check your connection.';
                    } else if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            message = response.data || message;
                        } catch(e) {
                            // Use default message
                        }
                    }
                    reject(new Error(message));
                },
                complete: function() {
                    // Failsafe: ensure button is always re-enabled
                    // This runs regardless of success or error
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    /**
     * Set preview mode
     */
    function setPreviewMode(mode) {
        $('.aisb-preview-toggle').removeClass('active');
        $('.aisb-preview-toggle[data-view="' + mode + '"]').addClass('active');
        
        var $canvas = $('.aisb-editor-canvas__inner');
        
        switch(mode) {
            case 'tablet':
                $canvas.css('max-width', '768px');
                break;
            case 'mobile':
                $canvas.css('max-width', '375px');
                break;
            default:
                $canvas.css('max-width', '1200px');
        }
    }
    
    /**
     * Update section list in right panel
     */
    function updateSectionList(skipSortableReinit) {
        var $list = $('#aisb-section-list');
        var $empty = $('.aisb-structure-empty');
        
        // Clear existing items
        $list.find('.aisb-section-item').remove();
        
        if (editorState.sections.length === 0) {
            $empty.show();
            $list.hide();
            return;
        }
        
        $empty.hide();
        $list.show();
        
        $.each(editorState.sections, function(index, section) {
            var title = section.content.headline || 'Untitled Section';
            var type = section.type.charAt(0).toUpperCase() + section.type.slice(1);
            var isActive = editorState.currentSection === index;
            var isReorderMode = editorState.reorderMode.active && editorState.reorderMode.selectedIndex === index;
            
            var itemHtml = `
                <div class="aisb-section-item ${isActive ? 'active' : ''} ${isReorderMode ? 'reorder-mode' : ''}" 
                     data-index="${index}"
                     role="listitem"
                     tabindex="0"
                     aria-describedby="aisb-reorder-instructions"
                     aria-label="${type} section: ${title}. Position ${index + 1} of ${editorState.sections.length}">
                    <div class="aisb-section-item__drag" 
                         role="button" 
                         tabindex="-1"
                         aria-label="Drag to reorder or use Enter to activate keyboard reordering"
                         title="Drag to reorder or use Enter to activate keyboard reordering">
                        <span class="dashicons dashicons-sort" aria-hidden="true"></span>
                    </div>
                    <div class="aisb-section-item__icon" aria-hidden="true">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="aisb-section-item__content">
                        <div class="aisb-section-item__title">${title}</div>
                        <div class="aisb-section-item__type" aria-label="Section type">${type}</div>
                    </div>
                    <div class="aisb-section-item__actions">
                        <button class="aisb-section-item__action delete" 
                                title="Delete ${title} section"
                                aria-label="Delete ${title} section">
                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            `;
            
            $list.append(itemHtml);
        });
        
        // Only reinitialize Sortable.js if not skipping
        if (!skipSortableReinit) {
            reinitializeSortable();
        }
    }
    
    /**
     * Handle keyboard navigation in section list
     */
    function handleSectionKeyNavigation(e, $item) {
        var index = parseInt($item.data('index'));
        
        // Handle reorder mode
        if (editorState.reorderMode.active && editorState.reorderMode.selectedIndex === index) {
            switch (e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    moveSection(index, index - 1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    moveSection(index, index + 1);
                    break;
                case 'Enter':
                    e.preventDefault();
                    exitReorderMode();
                    var successMsg = (window.aisbEditor && window.aisbEditor.i18n.sectionMoved) || 'Section moved successfully';
                    showNotification(successMsg);
                    break;
                case 'Escape':
                    e.preventDefault();
                    exitReorderMode();
                    var cancelMsg = (window.aisbEditor && window.aisbEditor.i18n.reorderCancelled) || 'Reorder cancelled';
                    showNotification(cancelMsg);
                    break;
            }
            return;
        }
        
        // Normal navigation
        switch (e.key) {
            case 'ArrowUp':
                e.preventDefault();
                focusPreviousSection($item);
                break;
            case 'ArrowDown':
                e.preventDefault();
                focusNextSection($item);
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                // Click the section to edit it
                $item.trigger('click');
                break;
            case 'Delete':
            case 'Backspace':
                e.preventDefault();
                deleteSection(index);
                break;
        }
    }
    
    /**
     * Toggle reorder mode for a section
     */
    function toggleReorderMode(index) {
        if (editorState.reorderMode.active) {
            exitReorderMode();
        } else {
            enterReorderMode(index);
        }
    }
    
    /**
     * Enter reorder mode
     */
    function enterReorderMode(index) {
        editorState.reorderMode.active = true;
        editorState.reorderMode.selectedIndex = index;
        updateSectionList();
        
        // Announce to screen readers
        var message = (window.aisbEditor && window.aisbEditor.i18n.reorderMode) || 
                     'Reorder mode activated. Use arrow keys to move section, Enter to confirm, Escape to cancel.';
        announceToScreenReader(message);
    }
    
    /**
     * Exit reorder mode
     */
    function exitReorderMode() {
        editorState.reorderMode.active = false;
        editorState.reorderMode.selectedIndex = null;
        updateSectionList();
    }
    
    /**
     * Move section from one position to another
     */
    function moveSection(fromIndex, toIndex) {
        // Validate bounds
        if (toIndex < 0 || toIndex >= editorState.sections.length) {
            return;
        }
        
        // Perform the move
        var section = editorState.sections.splice(fromIndex, 1)[0];
        editorState.sections.splice(toIndex, 0, section);
        
        // Update reorder mode index
        editorState.reorderMode.selectedIndex = toIndex;
        
        // Mark as dirty to show unsaved changes
        editorState.isDirty = true;
        
        // Re-render everything
        renderSections();
        
        // Trigger auto-save
        triggerAutoSave();
        
        // Focus the moved item
        setTimeout(function() {
            $('.aisb-section-item[data-index="' + toIndex + '"]').focus();
        }, 100);
    }
    
    /**
     * Focus previous section in list
     */
    function focusPreviousSection($currentItem) {
        var $prev = $currentItem.prev('.aisb-section-item');
        if ($prev.length) {
            $prev.focus();
        }
    }
    
    /**
     * Focus next section in list
     */
    function focusNextSection($currentItem) {
        var $next = $currentItem.next('.aisb-section-item');
        if ($next.length) {
            $next.focus();
        }
    }
    
    /**
     * Announce message to screen readers
     */
    function announceToScreenReader(message) {
        var $announcer = $('#aisb-screen-reader-announcer');
        if (!$announcer.length) {
            $announcer = $('<div id="aisb-screen-reader-announcer" aria-live="polite" class="screen-reader-text"></div>');
            $('body').append($announcer);
        }
        $announcer.text(message);
    }
    
    /**
     * Show notification to user
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        // For now, just announce to screen reader
        // In future, could add visual notification
        announceToScreenReader(message);
        
        // Also log to console for debugging
        console.log('AISB Notification (' + type + '):', message);
    }
    
    /**
     * Delete section
     */
    function deleteSection(index) {
        if (confirm('Are you sure you want to delete this section?')) {
            editorState.sections.splice(index, 1);
            editorState.isDirty = true;
            
            // If we're editing the deleted section, go back to library
            if (editorState.currentSection === index) {
                showLibraryMode();
            } else if (editorState.currentSection > index) {
                // Adjust current section index if needed
                editorState.currentSection--;
            }
            
            renderSections();
            
            // Update save button to show unsaved changes
            updateSaveStatus('unsaved');
        }
    }
    
    // Debug helper for testing media system
    window.debugMediaSystem = function() {
        console.log('=== MEDIA SYSTEM DEBUG ===');
        console.log('Current Section Index:', editorState.currentSection);
        
        if (editorState.currentSection !== null) {
            var content = editorState.sections[editorState.currentSection].content;
            console.log('Current Section Content:', content);
            console.log('Media Type:', content.media_type);
            console.log('Featured Image:', content.featured_image);
            console.log('Video URL:', content.video_url);
            
            // Check what renderMediaPreview would return
            var preview = renderMediaPreview(content);
            console.log('renderMediaPreview Output:', preview);
            
            // Check DOM
            var $mediaInPreview = $('.aisb-section[data-index="' + editorState.currentSection + '"] .aisb-hero__media');
            console.log('Media element in preview exists:', $mediaInPreview.length > 0);
            if ($mediaInPreview.length > 0) {
                console.log('Media element HTML:', $mediaInPreview[0].outerHTML);
            }
        } else {
            console.log('No section currently selected');
        }
        
        console.log('=== END DEBUG ===');
    };
    
    /**
     * Initialize URL autocomplete using jQuery UI
     */
    function initializeUrlAutocomplete() {
        // Check if jQuery UI autocomplete is available
        if (!$.fn.autocomplete) {
            return; // Silently fail if not loaded
        }
        
        $('.aisb-url-autocomplete').each(function() {
            var $input = $(this);
            
            // Skip if already initialized
            if ($input.hasClass('ui-autocomplete-input')) {
                return;
            }
            
            $input.autocomplete({
                minLength: 2,
                delay: 300,
                source: function(request, response) {
                    $.ajax({
                        url: aisbEditor.restUrl + 'search-content',
                        type: 'GET',
                        dataType: 'json',
                        headers: {
                            'X-WP-Nonce': aisbEditor.restNonce
                        },
                        data: {
                            search: request.term,
                            per_page: 10
                        },
                        success: function(data) {
                            if (data && data.results) {
                                var items = $.map(data.results, function(item) {
                                    if (item.type === 'custom') {
                                        return null; // Skip the custom option
                                    }
                                    return {
                                        label: item.text,
                                        value: item.url
                                    };
                                });
                                response(items);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]); // Return empty results on error
                        }
                    });
                },
                select: function(event, ui) {
                    // Update the input with the URL
                    $(this).val(ui.item.value).trigger('input');
                    
                    // Check if external link and auto-check "open in new tab"
                    if (ui.item.value && ui.item.value.startsWith('http') && 
                        !ui.item.value.includes(window.location.hostname)) {
                        $(this).closest('.aisb-repeater-field-group')
                            .find('[data-field="target"]')
                            .prop('checked', true);
                    }
                    
                    return false; // Prevent default behavior
                },
                focus: function(event, ui) {
                    // Show the label in the input while navigating
                    $(this).val(ui.item.value);
                    return false;
                }
            });
            
            // Custom render item if autocomplete was successfully initialized
            var autocompleteInstance = $input.data('ui-autocomplete');
            if (autocompleteInstance) {
                autocompleteInstance._renderItem = function(ul, item) {
                    return $('<li>')
                        .append('<div class="aisb-autocomplete-item">' + item.label + '</div>')
                        .appendTo(ul);
                };
            }
        });
    }
    
    // Initialize autocomplete whenever buttons are rendered
    $(document).on('aisb:repeater:item-added aisb:repeater:items-rendered', function() {
        setTimeout(initializeUrlAutocomplete, 100);
    });
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.aisb-editor-wrapper').length) {
            console.log('AISB: Initializing editor...');
            console.log('Debug mode enabled. Use window.debugMediaSystem() in console to check media state.');
            initEditor();
            
            // Initialize autocomplete on any existing URL fields
            setTimeout(initializeUrlAutocomplete, 500);
            
            console.log('AISB: Editor initialization complete');
        }
    });
    
})(jQuery);
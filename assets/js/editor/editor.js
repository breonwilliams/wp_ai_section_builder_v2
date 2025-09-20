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
        isSaving: false, // Track save state for beforeunload
        globalSettingsDirty: false, // Track global settings changes
        reorderMode: {
            active: false,
            selectedIndex: null,
            targetIndex: null
        },
        lastSaved: null,
        sortableInstance: null,
        sortableInitializing: false, // Track initialization state
        autoSaveTimer: null, // Auto-save timer reference
        debug: false // Debug mode - set to true to troubleshoot
    };
    
    // Expose editorState globally for integration
    window.editorState = editorState;
    
    // Debug helper
    function debugLog(context, data) {
        if (editorState.debug) {
            // console.log(`[AISB DEBUG - ${context}]:`, data);
        }
    }
    
    /**
     * Initialize editor
     */
    function initEditor() {
        debugLog('Initialization', 'Initializing editor');
        
        // Initialize drag-drop capabilities
        initDragDropCapabilities();
        
        // Load existing sections if any
        loadSections();
        
        // Bind events
        bindEvents();
        
        // Initialize sidebar toggle
        initSidebarToggle();
        
        // Initialize save protection
        initSaveProtection();
        
        debugLog('Editor initialization complete');
    }
    
    /**
     * Initialize drag-drop capabilities
     */
    function initDragDropCapabilities() {
        // Check if drag-drop should be enabled
        var features = (window.aisbEditor && window.aisbEditor.features) || { dragDrop: true }; // Default to enabled
        
        debugLog('Features config:', features);
        debugLog('Sortable.js available:', typeof Sortable !== 'undefined');
        
        if (features.dragDrop !== false) { // Enable by default unless explicitly disabled
            // Check if Sortable.js is available
            if (typeof Sortable === 'undefined') {
                console.warn('AISB: Sortable.js not loaded, attempting fallback...');
                // Give it one more chance after a delay
                setTimeout(function() {
                    if (typeof Sortable !== 'undefined') {
                        debugLog('Sortable.js loaded after delay');
                        initSortableJS();
                    } else {
                        console.error('AISB: Sortable.js failed to load completely');
                        showNotification('Drag-drop unavailable. Use keyboard navigation.', 'warning');
                    }
                }, 500);
                return;
            }
            
            // Initialize Sortable.js with error boundary
            try {
                debugLog('Initializing Sortable.js drag-drop');
                initSortableJS();
            } catch (error) {
                console.error('AISB: Sortable.js initialization failed:', error);
                showNotification('Drag-drop initialization failed. Keyboard navigation available.', 'error');
            }
        } else {
            debugLog('Drag-drop explicitly disabled in features');
        }
    }
    
    /**
     * Initialize Sortable.js functionality
     */
    function initSortableJS() {
        // Prevent multiple initialization attempts
        if (editorState.sortableInitializing) {
            debugLog('Sortable initialization already in progress');
            return;
        }
        
        var retryCount = 0;
        var maxRetries = 30; // 3 seconds max wait
        
        // Wait for DOM to be ready and section list to exist
        function setupSortable() {
            var sectionList = document.getElementById('aisb-section-list');
            
            if (!sectionList) {
                retryCount++;
                if (retryCount > maxRetries) {
                    console.error('AISB: Section list not found after ' + maxRetries + ' attempts');
                    editorState.sortableInitializing = false;
                    return;
                }
                console.warn('AISB: Section list not found, retry ' + retryCount + '/' + maxRetries);
                setTimeout(setupSortable, 100);
                return;
            }
            
            // Mark as initializing
            editorState.sortableInitializing = true;
            
            try {
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
                editorState.sortableInitializing = false;
                
                // Debug: Log sortable configuration
                debugLog('Sortable initialized successfully:', {
                    container: sectionList.id,
                    childCount: sectionList.children.length,
                    draggableSelector: '.aisb-section-item'
                });
                
            } catch (error) {
                console.error('AISB: Failed to initialize Sortable:', error);
                editorState.sortableInitializing = false;
                showNotification('Drag-drop initialization failed. Using keyboard navigation.', 'warning');
            }
        }
        
        setupSortable();
    }
    
    /**
     * Handle drag start event
     */
    function handleDragStart(evt) {
        var draggedIndex = parseInt(evt.oldIndex);
        
        // Validate index
        if (isNaN(draggedIndex) || draggedIndex < 0) {
            console.error('AISB: Invalid drag index:', evt.oldIndex);
            return;
        }
        
        // Store drag state
        editorState.reorderMode.active = true;
        editorState.reorderMode.selectedIndex = draggedIndex;
        
        // Add visual feedback
        document.body.classList.add('aisb-dragging');
        
        // Get section info for debugging
        var section = editorState.sections[draggedIndex];
        var sectionTitle = section ? (section.content.heading || 'Untitled') : 'Unknown';
        
        // Announce to screen readers
        announceToScreenReader('Dragging section: ' + sectionTitle + '. Move to desired position and release.');
        
        debugLog('Drag started for section', draggedIndex, '(' + sectionTitle + ')');
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
        
        debugLog('Drag ended - oldIndex:', oldIndex, 'newIndex:', newIndex);
        
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
            
            var sectionTitle = section.content.heading || 'Untitled Section';
            editorState.sections.splice(newIndex, 0, section);
            
            debugLog('Moved section "' + sectionTitle + '" from position', oldIndex + 1, 'to', newIndex + 1);
            
            // Mark as dirty to show unsaved changes
            editorState.isDirty = true;
            
            // Update canvas to reflect new order (but avoid rebuilding section list)
            renderCanvasOnly();
            
            // Update section list with new order
            updateSectionListOrder();
            
            // Update save button to indicate unsaved changes
            updateSaveStatus('unsaved');
            
            // Announce success
            var successMsg = 'Section "' + sectionTitle + '" moved to position ' + (newIndex + 1);
            announceToScreenReader(successMsg);
            showNotification(successMsg, 'success');
            
            debugLog('Section reorder completed successfully');
        } else {
            debugLog('Drag cancelled, no position change');
        }
    }
    
    /**
     * Reinitialize Sortable.js after DOM changes
     */
    function reinitializeSortable() {
        debugLog('Reinitializing Sortable.js');
        
        // Clean up existing instance safely
        if (editorState.sortableInstance) {
            try {
                // Check if the instance and its element still exist
                if (editorState.sortableInstance.el && editorState.sortableInstance.destroy) {
                    editorState.sortableInstance.destroy();
                    debugLog('Existing Sortable instance destroyed');
                }
            } catch (error) {
                console.warn('AISB: Error destroying Sortable instance:', error);
            }
            editorState.sortableInstance = null;
        }
        
        // Reset initialization flag
        editorState.sortableInitializing = false;
        
        // Reinitialize if drag-drop is enabled
        var features = (window.aisbEditor && window.aisbEditor.features) || {};
        var sortableContainer = document.getElementById('aisb-section-list'); // Fixed selector
        
        if (features.dragDrop && typeof Sortable !== 'undefined' && sortableContainer) {
            debugLog('Section list found, reinitializing drag-drop');
            setTimeout(initSortableJS, 50); // Small delay for DOM updates
        } else {
            if (!features.dragDrop) {
                debugLog('Drag-drop disabled in features');
            }
            if (typeof Sortable === 'undefined') {
                console.warn('AISB: Sortable.js not loaded');
            }
            if (!sortableContainer) {
                console.warn('AISB: Section list container not found');
            }
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
                var rawSections = JSON.parse(existingSections);
                
                // Normalize sections data for consistent handling
                if (window.AISBDataNormalizer) {
                    editorState.sections = window.AISBDataNormalizer.normalizeSections(rawSections);
                    console.log('Editor: Normalized', editorState.sections.length, 'sections from database');
                } else {
                    editorState.sections = rawSections;
                    console.warn('Editor: DataNormalizer not available, using raw sections');
                }
                
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
        // Panel tab switching (Sections vs Settings)
        $('.aisb-panel-tab').on('click', function() {
            var panelId = $(this).data('panel');
            
            // Update tab active states
            $('.aisb-panel-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding panel
            $('.aisb-panel-content').removeClass('active').hide();
            $('#aisb-panel-' + panelId).addClass('active').show();
        });
        
        // FAQ Accordion click handler for preview - uses CSS for animation
        $(document).on('click', '.aisb-faq__item-question', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $question = $(this);
            var $item = $question.closest('.aisb-faq__item');
            var isExpanded = $item.hasClass('aisb-faq__item--expanded');
            
            // Toggle expanded state - CSS handles the animation
            if (isExpanded) {
                $item.removeClass('aisb-faq__item--expanded');
            } else {
                $item.addClass('aisb-faq__item--expanded');
            }
        });
        
        // Add section button - auto-add with defaults
        $('.aisb-section-type').on('click', function() {
            var sectionType = $(this).data('type');
            
            // Get appropriate defaults based on section type
            var sectionDefaults;
            if (sectionType === 'hero') {
                sectionDefaults = heroDefaults;
            } else if (sectionType === 'hero-form') {
                sectionDefaults = heroFormDefaults;
            } else if (sectionType === 'features') {
                sectionDefaults = featuresDefaults;
            } else if (sectionType === 'checklist') {
                sectionDefaults = checklistDefaults;
            } else if (sectionType === 'faq') {
                sectionDefaults = faqDefaults;
            } else if (sectionType === 'stats') {
                sectionDefaults = statsDefaults;
            } else if (sectionType === 'testimonials') {
                sectionDefaults = testimonialsDefaults;
            } else {
                // Fallback to hero defaults for unknown types
                sectionDefaults = heroDefaults;
            }
            
            // Auto-add section with defaults
            var section = {
                type: sectionType,
                content: Object.assign({}, sectionDefaults)
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
        $(document).on('click', '.aisb-section', function(e) {
            // Don't open edit if clicking on FAQ question
            if ($(e.target).closest('.aisb-faq__item-question').length) {
                return;
            }
            
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
        
        // Clear all sections button
        $('#aisb-clear-all-sections').on('click', function() {
            clearAllSections();
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
        } else if (sectionType === 'hero-form') {
            formHtml = generateHeroFormForm(sectionContent);
        } else if (sectionType === 'features') {
            formHtml = generateFeaturesForm(sectionContent);
        } else if (sectionType === 'checklist') {
            formHtml = generateChecklistForm(sectionContent);
        } else if (sectionType === 'faq') {
            formHtml = generateFaqForm(sectionContent);
        } else if (sectionType === 'stats') {
            formHtml = generateStatsForm(sectionContent);
        } else if (sectionType === 'testimonials') {
            formHtml = generateTestimonialsForm(sectionContent);
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
            if (sectionType === 'hero') {
                initWysiwygEditors();
            } else if (sectionType === 'hero-form') {
                initWysiwygEditors(); // Hero-form uses same editors as hero
            } else if (sectionType === 'features') {
                initFeaturesWysiwygEditors();
            } else if (sectionType === 'checklist') {
                initChecklistWysiwygEditors();
            } else if (sectionType === 'faq') {
                initFaqWysiwygEditors();
            } else if (sectionType === 'stats') {
                initStatsWysiwygEditors();
            } else if (sectionType === 'testimonials') {
                initTestimonialsWysiwygEditors();
            }
        }, 100);
        
        // Initialize global blocks repeater for sections that support it
        if (sectionType === 'hero') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || heroDefaults;
            // Migrate old structure if needed
            content = migrateOldFieldNames(content);
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initGlobalBlocksRepeater(content, sectionType);
            }, 50);
        } else if (sectionType === 'hero-form') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || heroFormDefaults;
            // Migrate old structure if needed
            content = migrateOldFieldNames(content);
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initGlobalBlocksRepeater(content, sectionType);
            }, 50);
        } else if (sectionType === 'features') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || featuresDefaults;
            // Initialize both repeaters for features
            setTimeout(function() {
                initCardsRepeater(content);
                initGlobalBlocksRepeater(content, 'features');
            }, 50);
        } else if (sectionType === 'checklist') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || checklistDefaults;
            // Phase 1: Only initialize global blocks (buttons)
            // Phase 2: Will add items repeater
            setTimeout(function() {
                initGlobalBlocksRepeater(content, 'checklist');
                initChecklistItemsRepeater(content);
            }, 50);
        } else if (sectionType === 'faq') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || faqDefaults;
            // Initialize both global blocks and FAQ items repeater
            setTimeout(function() {
                initGlobalBlocksRepeater(content, 'faq');
                initFaqItemsRepeater(content);
            }, 50);
        } else if (sectionType === 'stats') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || statsDefaults;
            // Initialize both stats repeater and global blocks
            setTimeout(function() {
                initStatsRepeater(content);
                initGlobalBlocksRepeater(content, 'stats');
            }, 50);
        } else if (sectionType === 'testimonials') {
            // Use sectionContent if editing, otherwise use defaults
            var content = sectionContent || testimonialsDefaults;
            // Initialize global blocks (Phase 2 will add testimonials repeater)
            setTimeout(function() {
                initGlobalBlocksRepeater(content, 'testimonials');
                initTestimonialsRepeater(content);
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
            // Parse buttons if it's a JSON string
            var buttons = content.buttons;
            if (typeof buttons === 'string') {
                try {
                    buttons = JSON.parse(buttons);
                } catch (e) {
                    console.error('Failed to parse buttons:', e);
                    buttons = [];
                }
            }
            
            // Ensure buttons is an array
            if (!Array.isArray(buttons)) {
                buttons = [];
            }
            
            migrated.global_blocks = buttons.map(function(btn) {
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
     * Hero Form section field defaults - Exactly like Hero but without media
     */
    var heroFormDefaults = {
        // Standard content fields
        eyebrow_heading: 'Get Started Today',
        heading: 'Contact Us',
        content: '<p>Fill out the form to get in touch with our team.</p>',
        outro_content: '',
        
        // Form fields for hero-form
        form_type: 'placeholder', // 'placeholder' | 'shortcode'
        form_shortcode: '', // e.g., '[contact-form-7 id="123"]'
        
        // Global blocks for nested components
        global_blocks: [
            {
                type: 'button',
                text: 'Learn More',
                url: '#',
                style: 'primary'
            },
            {
                type: 'button',
                text: 'View Pricing',
                url: '#',
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
     * Features section field defaults - Same structure as Hero for consistency
     */
    var featuresDefaults = {
        // Standard content fields (SAME field names as Hero for AI consistency)
        eyebrow_heading: 'Features',
        heading: 'Everything You Need to Succeed',
        content: '<p>Our comprehensive platform provides all the tools and features you need to achieve your goals.</p>',
        outro_content: '',
        
        // Media fields (for future use, keeping structure consistent)
        media_type: 'none',
        featured_image: '',
        video_url: '',
        
        // Cards array with sample feature cards
        cards: [
            {
                id: 'feature_1',
                image: '',
                heading: 'Lightning Fast',
                content: 'Experience blazing fast performance with our optimized infrastructure and cutting-edge technology.',
                link: '',
                link_text: 'Learn More',
                link_target: '_self'
            },
            {
                id: 'feature_2',
                image: '',
                heading: 'Secure & Reliable',
                content: 'Your data is protected with enterprise-grade security and 99.9% uptime guarantee.',
                link: '',
                link_text: 'Learn More',
                link_target: '_self'
            },
            {
                id: 'feature_3',
                image: '',
                heading: 'Easy Integration',
                content: 'Get up and running in minutes with our simple setup process and comprehensive documentation.',
                link: '',
                link_text: 'Learn More',
                link_target: '_self'
            }
        ],
        
        // Global blocks for buttons (matching Hero structure)
        global_blocks: [],
        
        // Variant fields
        theme_variant: 'light',  // Default to light to alternate with Hero's dark
        layout_variant: 'content-left',  // Will change to grid layouts later
        card_alignment: 'left',  // Card content alignment: 'left' | 'center'
        
        // CTA fields (for future use)
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: ''
    };
    
    /**
     * Checklist section field defaults - Same structure as Features
     */
    var checklistDefaults = {
        // Standard content fields (SAME field names as Features)
        eyebrow_heading: 'Why Choose Us',
        heading: 'Everything You Need',
        content: '<p>Our comprehensive solution provides everything you need to succeed, with features designed to help you achieve your goals.</p>',
        outro_content: '',
        
        // Media fields (same as Features)
        media_type: 'none',
        featured_image: '',
        video_url: '',
        
        // Items array with sample checklist items
        items: [
            {
                id: 'checklist_1',
                heading: 'Professional Design',
                content: 'Custom layouts and designs tailored to match your brand identity and vision.'
            },
            {
                id: 'checklist_2',
                heading: 'Mobile Responsive',
                content: 'Looks perfect on all devices, from desktop computers to smartphones and tablets.'
            },
            {
                id: 'checklist_3',
                heading: 'SEO Optimized',
                content: 'Built with search engines in mind to help you rank higher and reach more customers.'
            },
            {
                id: 'checklist_4',
                heading: '24/7 Support',
                content: 'Our dedicated support team is here to help you whenever you need assistance.'
            }
        ],
        
        // Global blocks for buttons (same as Features)
        global_blocks: [],
        
        // Variant fields (same as Features)
        theme_variant: 'light',
        layout_variant: 'content-left',  // 'content-left' | 'center' | 'content-right'
        
        // CTA fields (for future use, same as Features)
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: ''
    };
    
    /**
     * FAQ section field defaults - Based on Checklist structure
     */
    var faqDefaults = {
        // Standard content fields (SAME field names as Checklist)
        eyebrow_heading: 'FAQs',
        heading: 'Frequently Asked Questions',
        content: '<p>Find answers to common questions about our products and services.</p>',
        outro_content: '<p>Still have questions? <a href="#contact">Contact our support team</a> for personalized assistance.</p>',
        
        // Media fields (same as Checklist)
        media_type: 'none',
        featured_image: '',
        video_url: '',
        
        // FAQ items array with sample questions and answers
        faq_items: [
            {
                id: 'faq_1',
                question: 'How do I get started?',
                answer: '<p>Getting started is easy! Simply sign up for an account, choose your plan, and follow our step-by-step setup guide. Our onboarding process takes less than 5 minutes, and you\'ll have access to all features immediately.</p>'
            },
            {
                id: 'faq_2',
                question: 'What\'s included in the package?',
                answer: '<p>Our package includes access to all core features, regular updates, comprehensive documentation, and email support. Premium plans also include priority support, advanced features, and custom integrations.</p>'
            },
            {
                id: 'faq_3',
                question: 'Do you offer customer support?',
                answer: '<p>Yes! We provide comprehensive customer support through multiple channels. All plans include email support with 24-48 hour response times. Premium plans receive priority support with faster response times and access to live chat.</p>'
            },
            {
                id: 'faq_4',
                question: 'Can I cancel my subscription anytime?',
                answer: '<p>Absolutely! You can cancel your subscription at any time from your account dashboard. There are no cancellation fees, and you\'ll continue to have access until the end of your current billing period.</p>'
            }
        ],
        
        // Global blocks for buttons (same as Checklist)
        global_blocks: [],
        
        // Display options (same as Checklist)
        theme_variant: 'light',  // 'light' | 'dark'
        layout_variant: 'center',  // 'content-left' | 'center' | 'content-right'
        
        // CTA fields (for future use, same as Checklist)
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: ''
    };
    
    /**
     * Stats section field defaults - Phase 1: Core structure without items
     */
    var statsDefaults = {
        // Standard content fields (SAME field names as Features)
        eyebrow_heading: '',
        heading: 'By the Numbers',
        content: '<p>Our impact and achievements</p>',
        outro_content: '',
        
        // Media fields (SAME as Features)
        media_type: 'none',  // 'none' | 'image' | 'video'
        featured_image: '',
        video_url: '',
        
        // Stats items array with sample stats
        stats: [
            {
                id: 'stat_1',
                number: '99%',
                label: 'Customer Satisfaction',
                description: 'Based on 10,000+ reviews'
            },
            {
                id: 'stat_2',
                number: '50M+',
                label: 'Active Users',
                description: 'Across 120 countries'
            },
            {
                id: 'stat_3',
                number: '24/7',
                label: 'Support Available',
                description: 'Expert help when you need it'
            },
            {
                id: 'stat_4',
                number: '5★',
                label: 'Average Rating',
                description: 'On all major platforms'
            }
        ],
        
        // Global blocks support (for buttons)
        global_blocks: [],
        
        // Display options (same as Features)
        theme_variant: 'light',  // 'light' | 'dark'
        layout_variant: 'center',  // 'content-left' | 'center' | 'content-right'
        
        // CTA fields (for future use, same as Features)
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: ''
    };
    
    /**
     * Testimonials section defaults
     */
    var testimonialsDefaults = {
        // Standard content fields (SAME as Features for consistency)
        eyebrow_heading: 'Testimonials',
        heading: 'What Our Customers Say',
        content: '<p>Hear from real people who have achieved amazing results with our solution.</p>',
        outro_content: '',
        
        // Media fields (SAME as Features)
        media_type: 'none',
        featured_image: '',
        video_url: '',
        
        // Testimonials array with sample items
        testimonials: [
            {
                rating: 5, // 1-5 star rating
                content: 'This product has completely transformed our workflow. The results speak for themselves - we\'ve seen a 40% increase in productivity.',
                author_name: 'Sarah Johnson',
                author_title: 'CEO, TechStart Inc.',
                author_image: '' // Optional author image
            },
            {
                rating: 5,
                content: 'Outstanding service and support. The team went above and beyond to ensure our success. Highly recommended!',
                author_name: 'Michael Chen',
                author_title: 'Marketing Director, Growth Co.',
                author_image: ''
            },
            {
                rating: 5,
                content: 'We\'ve tried many solutions, but this is by far the best. Easy to use, powerful features, and excellent ROI.',
                author_name: 'Emily Rodriguez',
                author_title: 'Founder, Digital Agency',
                author_image: ''
            }
        ],
        
        // Variants (default to center for testimonials)
        theme_variant: 'light',
        layout_variant: 'center',
        
        // Global blocks for buttons
        global_blocks: []
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
     * Generate Hero Form section form - Exactly like Hero but without media
     */
    function generateHeroFormForm(content) {
        // Use existing content or defaults
        content = content || heroFormDefaults;
        
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
                
                <!-- Form field for hero-form -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Form Settings
                    </label>
                    <div class="aisb-form-selector">
                        <!-- Form Type Selector -->
                        <div class="aisb-form-type-selector">
                            <label class="aisb-radio-label">
                                <input type="radio" name="form_type" value="placeholder" ${content.form_type === 'placeholder' || !content.form_type ? 'checked' : ''}>
                                <span>Placeholder</span>
                            </label>
                            <label class="aisb-radio-label">
                                <input type="radio" name="form_type" value="shortcode" ${content.form_type === 'shortcode' ? 'checked' : ''}>
                                <span>Shortcode</span>
                            </label>
                        </div>
                        
                        <!-- Shortcode Input (shown when form_type is 'shortcode') -->
                        <div class="aisb-form-shortcode-controls" style="${content.form_type === 'shortcode' ? '' : 'display:none'}">
                            <textarea name="form_shortcode" 
                                      class="aisb-editor-input" 
                                      placeholder="[contact-form-7 id=&quot;123&quot;]"
                                      rows="2">${escapeHtml(content.form_shortcode || '')}</textarea>
                            <p class="aisb-editor-form-help">Enter your form shortcode (e.g., Contact Form 7, WPForms, Gravity Forms)</p>
                        </div>
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Global Blocks
                    </label>
                    <div id="hero-form-global-blocks" class="aisb-repeater-container">
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
     * Apply Features section defaults to ensure all required fields are present
     */
    function applyFeaturesDefaults(content) {
        if (!content) return featuresDefaults;
        
        // Start with defaults and merge content on top to preserve all fields
        var migrated = $.extend({}, featuresDefaults, content);
        
        // Ensure card_alignment has a default value for existing sections
        if (!migrated.card_alignment) {
            migrated.card_alignment = 'left';
        }
        
        return migrated;
    }
    
    /**
     * Generate Features section form - With UNIQUE IDs to avoid conflicts
     */
    function generateFeaturesForm(content) {
        // Use existing content or defaults
        content = content || featuresDefaults;
        
        // Apply migration to ensure all required fields are present
        content = applyFeaturesDefaults(content);
        
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
                    
                    <div class="aisb-variant-group">
                        <label class="aisb-editor-form-label">Card Alignment</label>
                        <div class="aisb-toggle-group">
                            <button type="button" class="aisb-toggle-btn ${content.card_alignment === 'left' ? 'active' : ''}" 
                                    data-variant-type="card_alignment" data-variant-value="left">
                                <span class="dashicons dashicons-editor-alignleft"></span> Left
                            </button>
                            <button type="button" class="aisb-toggle-btn ${content.card_alignment === 'center' ? 'active' : ''}" 
                                    data-variant-type="card_alignment" data-variant-value="center">
                                <span class="dashicons dashicons-editor-aligncenter"></span> Center
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content Fields with UNIQUE IDs -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="features-eyebrow-heading">
                        Eyebrow Heading
                    </label>
                    <input type="text" 
                           id="features-eyebrow-heading" 
                           name="eyebrow_heading" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.eyebrow_heading || '')}"
                           placeholder="Optional text above heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="features-heading">
                        Heading
                    </label>
                    <input type="text" 
                           id="features-heading" 
                           name="heading" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.heading || '')}"
                           placeholder="Section heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="features-content">
                        Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="features-content" 
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
                        Feature Cards
                    </label>
                    <div id="features-cards" class="aisb-repeater-container">
                        <!-- Cards repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Buttons
                    </label>
                    <div id="features-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="features-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="features-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Generate Checklist section form - Same structure as Features
     */
    function generateChecklistForm(content) {
        // Use existing content or defaults
        content = content || checklistDefaults;
        
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
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Eyebrow Heading
                    </label>
                    <input type="text" name="eyebrow_heading" 
                           value="${escapeHtml(content.eyebrow_heading || '')}" 
                           placeholder="Optional eyebrow text">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Heading
                    </label>
                    <input type="text" name="heading" 
                           value="${escapeHtml(content.heading || '')}" 
                           placeholder="Section heading" required>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="checklist-content">
                        Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="checklist-content" 
                                  name="content" 
                                  class="aisb-editor-wysiwyg">${content.content || ''}</textarea>
                    </div>
                </div>
                
                <!-- Media field - now visible for ALL layouts including center (matching hero/features) -->
                <div class="aisb-editor-form-group aisb-media-field">
                    <label class="aisb-editor-form-label">
                        Featured Image
                    </label>
                    ${generateMediaField(content)}
                </div>
                
                <!-- Phase 2: Checklist items will go here -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Checklist Items
                        <span class="aisb-editor-form-help">Add items to your checklist</span>
                    </label>
                    <div id="checklist-items" class="aisb-repeater-container"></div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Buttons
                    </label>
                    <div id="checklist-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="checklist-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="checklist-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Generate FAQ section form - Based on Checklist structure
     */
    function generateFaqForm(content) {
        // Use existing content or defaults
        content = content || faqDefaults;
        
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
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Eyebrow Heading
                    </label>
                    <input type="text" name="eyebrow_heading" 
                           value="${escapeHtml(content.eyebrow_heading || '')}" 
                           placeholder="Optional eyebrow text">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Heading
                    </label>
                    <input type="text" name="heading" 
                           value="${escapeHtml(content.heading || '')}" 
                           placeholder="Section heading" required>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="faq-content">
                        Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="faq-content" 
                                  name="content" 
                                  class="aisb-editor-wysiwyg">${content.content || ''}</textarea>
                    </div>
                </div>
                
                <!-- Media field - visible for ALL layouts -->
                <div class="aisb-editor-form-group aisb-media-field">
                    <label class="aisb-editor-form-label">
                        Featured Image
                    </label>
                    ${generateMediaField(content)}
                </div>
                
                <!-- FAQ items repeater -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        FAQ Items
                        <span class="aisb-editor-form-help">Add questions and answers</span>
                    </label>
                    <div id="faq-items" class="aisb-repeater-container">
                        <!-- FAQ items repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Buttons
                    </label>
                    <div id="faq-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="faq-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="faq-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Generate Stats section form - Phase 1: Core structure
     */
    function generateStatsForm(content) {
        // Use existing content or defaults
        content = content || statsDefaults;
        
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
                
                <!-- Basic Fields -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="stats-eyebrow">Eyebrow Text (Optional)</label>
                    <input type="text" 
                           id="stats-eyebrow" 
                           name="eyebrow_heading" 
                           value="${escapeHtml(content.eyebrow_heading || '')}" 
                           class="aisb-editor-input"
                           placeholder="Optional text above heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="stats-heading">Heading</label>
                    <input type="text" 
                           id="stats-heading" 
                           name="heading" 
                           value="${escapeHtml(content.heading || '')}" 
                           class="aisb-editor-input" 
                           placeholder="Section heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="stats-content">
                        Intro Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="stats-content" 
                                  name="content" 
                                  class="aisb-editor-wysiwyg">${content.content || ''}</textarea>
                    </div>
                </div>
                
                <!-- Media Field -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Featured Image
                    </label>
                    ${generateMediaField(content)}
                </div>
                
                <!-- Stats Items Repeater -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">Stats Items</label>
                    <div id="stats-items" class="aisb-repeater-container">
                        <!-- Stats repeater will be initialized here -->
                    </div>
                </div>
                
                <!-- Global Blocks (Buttons) -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">Buttons</label>
                    <div id="stats-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <!-- Outro Content -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="stats-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="stats-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Generate Testimonials section form
     */
    function generateTestimonialsForm(content) {
        // Use existing content or defaults
        content = content || testimonialsDefaults;
        
        return `
            <form id="aisb-section-form" class="aisb-editor-form">
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
                
                <!-- Basic Fields -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="testimonials-eyebrow">Eyebrow Text (Optional)</label>
                    <input type="text" 
                           id="testimonials-eyebrow" 
                           name="eyebrow_heading" 
                           value="${escapeHtml(content.eyebrow_heading || '')}" 
                           class="aisb-editor-input"
                           placeholder="Optional text above heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="testimonials-heading">Heading</label>
                    <input type="text" 
                           id="testimonials-heading" 
                           name="heading" 
                           value="${escapeHtml(content.heading || '')}" 
                           class="aisb-editor-input" 
                           placeholder="Section heading">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="testimonials-content">
                        Intro Content
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="testimonials-content" 
                                  name="content" 
                                  class="aisb-editor-wysiwyg">${content.content || ''}</textarea>
                    </div>
                </div>
                
                <!-- Media Field -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">
                        Featured Image
                    </label>
                    ${generateMediaField(content)}
                </div>
                
                <!-- Testimonials Repeater -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">Testimonials</label>
                    <div id="testimonials-items" class="aisb-repeater-container">
                        <!-- Testimonials repeater will be initialized here -->
                    </div>
                </div>
                
                <!-- Global Blocks (Buttons) -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label">Buttons</label>
                    <div id="testimonials-global-blocks" class="aisb-repeater-container">
                        <!-- Global blocks repeater will be initialized here -->
                    </div>
                </div>
                
                <!-- Outro Content -->
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="testimonials-outro-content">
                        Outro Content (Optional)
                    </label>
                    <div class="aisb-editor-wysiwyg-container">
                        <textarea id="testimonials-outro-content" 
                                  name="outro_content" 
                                  class="aisb-editor-wysiwyg">${content.outro_content || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
    }
    
    /**
     * Initialize Testimonials WYSIWYG editors
     */
    function initTestimonialsWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('testimonials-content');
            wp.editor.remove('testimonials-outro-content');
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('testimonials-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    height: 150,
                    setup: function(editor) {
                        editor.on('change keyup', function() {
                            editor.save();
                            updatePreview();
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
            
            // Outro content editor
            wp.editor.initialize('testimonials-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    height: 120,
                    setup: function(editor) {
                        editor.on('change keyup', function() {
                            editor.save();
                            updatePreview();
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        } else {
            // Fallback to plain textarea behavior
            $('#testimonials-content, #testimonials-outro-content').on('input', function() {
                updatePreview();
            });
        }
    }
    
    /**
     * Initialize testimonials repeater field
     */
    function initTestimonialsRepeater(content) {
        var $container = $('#testimonials-items');
        
        if (!$container.length) {
            return; // Container not found
        }
        
        // Get testimonials from content or use defaults
        var testimonials = content.testimonials || [];
        
        // Initialize repeater field
        var testimonialsRepeater = $container.aisbRepeaterField({
            fieldName: 'testimonials',
            items: testimonials,
            defaultItem: {
                rating: 5,
                content: 'This is an amazing product that has helped us achieve great results.',
                author_name: 'John Doe',
                author_title: 'CEO, Example Company',
                author_image: ''
            },
            maxItems: 12,
            minItems: 0,
            itemLabel: 'Testimonial',
            addButtonText: 'Add Testimonial',
            template: function(item, index) {
                // Generate star rating options
                var ratingOptions = '';
                for (var i = 1; i <= 5; i++) {
                    var selected = (item.rating == i) ? 'selected' : '';
                    var stars = '★'.repeat(i) + '☆'.repeat(5-i);
                    ratingOptions += `<option value="${i}" ${selected}>${stars}</option>`;
                }
                
                return `
                    <div class="aisb-repeater-fields">
                        <div class="aisb-repeater-field-group">
                            <label>Star Rating</label>
                            <select class="aisb-repeater-field aisb-editor-form-input" 
                                    data-field="rating">
                                ${ratingOptions}
                            </select>
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Testimonial Content</label>
                            <textarea class="aisb-repeater-field aisb-editor-form-input" 
                                      data-field="content" 
                                      rows="4"
                                      placeholder="Share your customer's experience...">${escapeHtml(item.content || '')}</textarea>
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Author Name</label>
                            <input type="text" 
                                   class="aisb-repeater-field aisb-editor-form-input" 
                                   data-field="author_name" 
                                   value="${escapeHtml(item.author_name || '')}"
                                   placeholder="e.g., Jane Smith">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Author Title/Position</label>
                            <input type="text" 
                                   class="aisb-repeater-field aisb-editor-form-input" 
                                   data-field="author_title" 
                                   value="${escapeHtml(item.author_title || '')}"
                                   placeholder="e.g., Marketing Director, Tech Corp">
                        </div>
                    </div>
                `;
            },
            onUpdate: function(testimonials) {
                debugLog('Testimonials updated', {
                    testimonials: testimonials,
                    currentSection: editorState.currentSection,
                    sectionType: editorState.currentSection !== null ? editorState.sections[editorState.currentSection].type : null
                });
                
                // Update the content
                if (editorState.currentSection !== null) {
                    editorState.sections[editorState.currentSection].content.testimonials = testimonials;
                    debugLog('Updated section content with testimonials', {
                        sectionContent: editorState.sections[editorState.currentSection].content
                    });
                    updatePreview();
                }
            }
        });
        
        return testimonialsRepeater;
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
    function initGlobalBlocksRepeater(content, sectionType) {
        // Default to hero if not specified for backward compatibility
        sectionType = sectionType || 'hero';
        
        // Get the appropriate container based on section type
        var containerId = sectionType + '-global-blocks';
        var $container = $('#' + containerId);
        if (!$container.length) {
            console.error('AISB: Global blocks container not found for ' + sectionType);
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
        console.log('=== globalBlockButtonTemplate CALLED ===');
        console.log('1. Button data:', JSON.parse(JSON.stringify(button)));
        console.log('2. Index:', index);
        console.log('3. Button text:', button.text);
        
        var styles = [
            { value: 'primary', label: 'Primary' },
            { value: 'secondary', label: 'Secondary' }
        ];
        
        var styleOptions = styles.map(function(style) {
            return '<option value="' + style.value + '"' + 
                   (button.style === style.value ? ' selected' : '') + '>' + 
                   style.label + '</option>';
        }).join('');
        
        var buttonText = button.text || '';
        console.log('4. Button text to display in input:', buttonText);
        
        return `
            <div class="aisb-repeater-fields">
                <div class="aisb-repeater-field-group">
                    <label>Button Text</label>
                    <input type="text" 
                           class="aisb-editor-input aisb-repeater-field" 
                           data-field="text" 
                           value="${escapeHtml(buttonText)}" 
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
     * Render Checklist Items
     */
    function renderChecklistItems(items) {
        // Parse items if it's a JSON string
        if (typeof items === 'string') {
            try {
                items = JSON.parse(items);
            } catch (e) {
                console.error('Failed to parse checklist items:', e);
                items = [];
            }
        }
        
        debugLog('renderChecklistItems called', {
            items: items,
            isArray: Array.isArray(items),
            length: items ? items.length : 0
        });
        
        if (!items || !Array.isArray(items) || items.length === 0) {
            debugLog('renderChecklistItems - no items to render');
            return '';
        }
        
        var itemsHtml = items.map(function(item) {
            var itemHeading = escapeHtml(item.heading || 'Checklist Item');
            var itemContent = escapeHtml(item.content || '');
            
            return `
                <div class="aisb-checklist__item">
                    <div class="aisb-checklist__item-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M7 12L10 15L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="aisb-checklist__item-content">
                        <h4 class="aisb-checklist__item-heading">${itemHeading}</h4>
                        ${itemContent ? `<p class="aisb-checklist__item-text">${itemContent}</p>` : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        return `<div class="aisb-checklist__items">${itemsHtml}</div>`;
    }
    
    /**
     * Render Feature Cards
     */
    function renderFeatureCards(cards) {
        // Parse cards if it's a JSON string
        if (typeof cards === 'string') {
            try {
                cards = JSON.parse(cards);
            } catch (e) {
                console.error('Failed to parse feature cards:', e);
                cards = [];
            }
        }
        
        if (!cards || !Array.isArray(cards) || cards.length === 0) {
            // Return empty - no placeholder cards
            return '';
        }
        
        var cardsHtml = cards.map(function(card) {
            var cardHeading = escapeHtml(card.heading || 'Feature Title');
            var cardContent = escapeHtml(card.content || '');
            
            // Build link with custom text and target
            var cardLink = '';
            if (card.link) {
                var linkText = escapeHtml(card.link_text || 'Learn More');
                var linkUrl = escapeHtml(card.link);
                var linkTarget = card.link_target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
                cardLink = `<a href="${linkUrl}" class="aisb-features__item-link"${linkTarget}>${linkText} →</a>`;
            }
            
            // Image with wrapper for aspect ratio
            var cardImage = '';
            if (card.image) {
                cardImage = `
                    <div class="aisb-features__item-image-wrapper">
                        <img src="${escapeHtml(card.image)}" alt="${escapeHtml(card.heading || '')}" class="aisb-features__item-image">
                    </div>
                `;
            }
            
            return `
                <div class="aisb-features__item">
                    ${cardImage}
                    <div class="aisb-features__item-content">
                        <h3 class="aisb-features__item-title">${cardHeading}</h3>
                        <p class="aisb-features__item-description">${cardContent}</p>
                        ${cardLink}
                    </div>
                </div>
            `;
        }).join('');
        
        return `<div class="aisb-features__grid">${cardsHtml}</div>`;
    }
    
    /**
     * Render stat items for Stats section
     */
    function renderStatItems(stats) {
        // Parse stats if it's a JSON string
        if (typeof stats === 'string') {
            try {
                stats = JSON.parse(stats);
            } catch (e) {
                console.error('Failed to parse stats:', e);
                stats = [];
            }
        }
        
        if (!stats || !Array.isArray(stats) || stats.length === 0) {
            // Return placeholder stats to show what the section will look like
            return `
                <div class="aisb-stats__grid">
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
                </div>
            `;
        }
        
        var statsHtml = stats.map(function(stat) {
            var statNumber = escapeHtml(stat.number || '0');
            var statLabel = escapeHtml(stat.label || 'Stat Label');
            var statDescription = stat.description ? `<p class="aisb-stats__item-description">${escapeHtml(stat.description)}</p>` : '';
            
            return `
                <div class="aisb-stats__item">
                    <div class="aisb-stats__item-number">${statNumber}</div>
                    <div class="aisb-stats__item-label">${statLabel}</div>
                    ${statDescription}
                </div>
            `;
        }).join('');
        
        return `<div class="aisb-stats__grid">${statsHtml}</div>`;
    }
    
    /**
     * Render testimonial items for Testimonials section
     */
    function renderTestimonialItems(testimonials) {
        // Parse testimonials if it's a JSON string
        if (typeof testimonials === 'string') {
            try {
                testimonials = JSON.parse(testimonials);
            } catch (e) {
                console.error('Failed to parse testimonials:', e);
                testimonials = [];
            }
        }
        
        if (!testimonials || !Array.isArray(testimonials) || testimonials.length === 0) {
            // Return placeholder message
            return `
                <div class="aisb-testimonials__grid">
                    <div class="aisb-testimonials__placeholder">
                        <p>Testimonials coming soon! Add your first testimonial to get started.</p>
                    </div>
                </div>
            `;
        }
        
        var testimonialsHtml = testimonials.map(function(item) {
            // Generate star rating HTML
            var starsHtml = '';
            var rating = parseInt(item.rating) || 5;
            for (var i = 1; i <= 5; i++) {
                if (i <= rating) {
                    starsHtml += '<span class="aisb-testimonials__star aisb-testimonials__star--filled">★</span>';
                } else {
                    starsHtml += '<span class="aisb-testimonials__star">☆</span>';
                }
            }
            
            return `
                <div class="aisb-testimonials__item">
                    <div class="aisb-testimonials__rating">
                        ${starsHtml}
                    </div>
                    <div class="aisb-testimonials__quote">
                        "${escapeHtml(item.content || 'Great experience!')}"
                    </div>
                    <div class="aisb-testimonials__author">
                        ${item.author_image ? `
                            <img src="${escapeHtml(item.author_image)}" 
                                 alt="${escapeHtml(item.author_name || 'Author')}" 
                                 class="aisb-testimonials__author-image">
                        ` : ''}
                        <div class="aisb-testimonials__author-info">
                            <div class="aisb-testimonials__author-name">
                                ${escapeHtml(item.author_name || 'Anonymous')}
                            </div>
                            ${item.author_title ? `
                                <div class="aisb-testimonials__author-title">
                                    ${escapeHtml(item.author_title)}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        return `<div class="aisb-testimonials__grid">${testimonialsHtml}</div>`;
    }
    
    /**
     * Initialize cards repeater for Features sections
     */
    function initCardsRepeater(content) {
        var $container = $('#features-cards');
        if (!$container.length) {
            return; // Container not found
        }
        
        // Get cards from content or empty array
        var cards = content.cards || [];
        
        // Initialize repeater field
        var cardsRepeater = $container.aisbRepeaterField({
            fieldName: 'cards',
            items: cards,
            defaultItem: {
                image: '',
                heading: 'Feature Title',
                content: 'Feature description goes here.',
                link: ''
            },
            maxItems: 9,
            minItems: 0,
            itemLabel: 'Card',
            addButtonText: 'Add Card',
            template: function(item, index) {
                var imageUrl = item.image || '';
                var hasImage = imageUrl ? true : false;
                
                return `
                    <div class="aisb-repeater-fields">
                        <div class="aisb-repeater-field-group">
                            <label>Image</label>
                            <div class="aisb-card-media-selector">
                                ${hasImage ? `
                                    <div class="aisb-card-media-preview">
                                        <img src="${escapeHtml(imageUrl)}" alt="Card image">
                                        <button type="button" class="aisb-card-media-remove" data-card-id="${item.id || ''}">
                                            <span class="dashicons dashicons-no-alt"></span> Remove
                                        </button>
                                    </div>
                                ` : ''}
                                <button type="button" class="aisb-editor-btn aisb-editor-btn-ghost aisb-editor-btn-with-icon select-card-image" 
                                        data-card-id="${item.id || ''}">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span>${hasImage ? 'Change Image' : 'Select Image'}</span>
                                </button>
                                <input type="hidden" class="aisb-repeater-field" data-field="image" 
                                       value="${escapeHtml(imageUrl)}">
                            </div>
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Heading</label>
                            <input type="text" class="aisb-repeater-field" data-field="heading" 
                                   value="${escapeHtml(item.heading || '')}" 
                                   placeholder="Feature Title">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Content</label>
                            <textarea class="aisb-repeater-field" data-field="content" 
                                      placeholder="Feature description...">${escapeHtml(item.content || '')}</textarea>
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Link Text</label>
                            <input type="text" class="aisb-repeater-field" data-field="link_text" 
                                   value="${escapeHtml(item.link_text || '')}" 
                                   placeholder="Learn More">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Link URL</label>
                            <input type="text" class="aisb-repeater-field aisb-url-autocomplete" data-field="link" 
                                   value="${escapeHtml(item.link || '')}" 
                                   placeholder="Start typing page name or enter URL">
                            <label class="aisb-checkbox-label" style="margin-top: 8px;">
                                <input type="checkbox" 
                                       class="aisb-repeater-field" 
                                       data-field="link_target" 
                                       value="_blank"
                                       ${item.link_target === '_blank' ? 'checked' : ''}>
                                <span>Open in new tab</span>
                            </label>
                        </div>
                    </div>
                `;
            },
            onUpdate: function(items) {
                // Update the section content with cards data
                if (editorState.currentSection !== null && editorState.sections[editorState.currentSection]) {
                    editorState.sections[editorState.currentSection].content.cards = items;
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
        
        return cardsRepeater;
    }
    
    /**
     * Initialize Checklist Items Repeater
     */
    function initChecklistItemsRepeater(content) {
        var $container = $('#checklist-items');
        if (!$container.length) {
            return; // Container not found
        }
        
        // Get items from content or empty array
        var items = content.items || [];
        
        // Initialize repeater field
        var itemsRepeater = $container.aisbRepeaterField({
            fieldName: 'items',
            items: items,
            defaultItem: {
                heading: 'Checklist Item',
                content: 'Description of what this includes or covers.'
            },
            maxItems: 10,
            minItems: 0,
            itemLabel: 'Item',
            addButtonText: 'Add Checklist Item',
            template: function(item, index) {
                return `
                    <div class="aisb-repeater-fields">
                        <div class="aisb-repeater-field-group">
                            <label>Item Heading</label>
                            <input type="text" 
                                   class="aisb-repeater-field aisb-editor-form-input" 
                                   data-field="heading" 
                                   value="${escapeHtml(item.heading || '')}"
                                   placeholder="e.g., 24/7 Support">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Item Description</label>
                            <textarea class="aisb-repeater-field aisb-editor-form-input" 
                                      data-field="content" 
                                      rows="3"
                                      placeholder="Describe this checklist item...">${escapeHtml(item.content || '')}</textarea>
                        </div>
                    </div>
                `;
            },
            onUpdate: function(items) {
                debugLog('Checklist items updated', {
                    items: items,
                    currentSection: editorState.currentSection,
                    sectionType: editorState.currentSection !== null ? editorState.sections[editorState.currentSection].type : null
                });
                
                // Update the content
                if (editorState.currentSection !== null) {
                    editorState.sections[editorState.currentSection].content.items = items;
                    debugLog('Updated section content with items', {
                        sectionContent: editorState.sections[editorState.currentSection].content
                    });
                    updatePreview();
                }
            }
        });
        
        return itemsRepeater;
    }
    
    /**
     * Initialize FAQ items repeater field
     */
    function initFaqItemsRepeater(content) {
        var $container = $('#faq-items');
        if (!$container.length) {
            return; // Container not found
        }
        
        // Get items from content - handle both 'questions' (from AI) and 'faq_items' (legacy)
        var items = content.questions || content.faq_items || [];
        
        // Initialize repeater field (keep internal fieldName as faq_items for consistency)
        var itemsRepeater = $container.aisbRepeaterField({
            fieldName: 'faq_items',
            items: items,
            defaultItem: {
                question: 'What is your question?',
                answer: '<p>This is the answer to your question.</p>'
            },
            maxItems: 20,
            minItems: 0,
            itemLabel: 'FAQ',
            addButtonText: 'Add FAQ Item',
            template: function(item, index) {
                // Use a consistent ID pattern for WYSIWYG
                var editorId = 'faq-answer-' + index;
                return `
                    <div class="aisb-repeater-fields">
                        <div class="aisb-repeater-field-group">
                            <label>Question</label>
                            <input type="text" 
                                   class="aisb-repeater-field aisb-editor-form-input" 
                                   data-field="question" 
                                   value="${escapeHtml(item.question || '')}"
                                   placeholder="e.g., How does your service work?">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Answer</label>
                            <textarea id="${editorId}"
                                      class="aisb-repeater-field aisb-editor-form-input aisb-faq-answer-wysiwyg" 
                                      data-field="answer" 
                                      data-index="${index}"
                                      rows="4"
                                      placeholder="Provide a detailed answer...">${item.answer || ''}</textarea>
                        </div>
                    </div>
                `;
            },
            onItemAdded: function($item, index) {
                // Initialize WYSIWYG for the new answer field
                setTimeout(function() {
                    initFaqAnswerEditor(index);
                }, 100);
            },
            onUpdate: function(items) {
                debugLog('FAQ items updated', {
                    items: items,
                    currentSection: editorState.currentSection,
                    sectionType: editorState.currentSection !== null ? editorState.sections[editorState.currentSection].type : null
                });
                
                // Update the content - save as 'questions' for AI compatibility
                if (editorState.currentSection !== null) {
                    // Save as both 'questions' (for AI/frontend) and 'faq_items' (for legacy)
                    editorState.sections[editorState.currentSection].content.questions = items;
                    editorState.sections[editorState.currentSection].content.faq_items = items;
                    debugLog('Updated section content with FAQ items', {
                        questions: items,
                        sectionContent: editorState.sections[editorState.currentSection].content
                    });
                    updatePreview();
                    
                    // Re-initialize WYSIWYG editors for all items after DOM update
                    setTimeout(function() {
                        items.forEach(function(item, index) {
                            initFaqAnswerEditor(index);
                        });
                    }, 300);
                }
            },
            onItemAdded: function(item, index) {
                // Initialize WYSIWYG for new item
                setTimeout(function() {
                    initFaqAnswerEditor(index);
                }, 100);
            }
        });
        
        // Initialize WYSIWYG for existing items with more delay
        items.forEach(function(item, index) {
            setTimeout(function() {
                initFaqAnswerEditor(index);
            }, 200 + (100 * index));
        });
        
        return itemsRepeater;
    }
    
    /**
     * Initialize WYSIWYG editor for FAQ answer field
     */
    function initFaqAnswerEditor(index) {
        var editorId = 'faq-answer-' + index;
        var $textarea = $('.aisb-faq-answer-wysiwyg[data-index="' + index + '"]');
        
        // Also check by ID directly
        if (!$textarea.length) {
            $textarea = $('#' + editorId);
        }
        
        // Also check for the old class name for compatibility
        if (!$textarea.length) {
            $textarea = $('.aisb-faq-answer[data-index="' + index + '"]');
        }
        
        if (!$textarea.length) {
            console.log('FAQ answer textarea not found for index:', index);
            return;
        }
        
        // Make sure the textarea has the correct ID
        if ($textarea.attr('id') !== editorId) {
            $textarea.attr('id', editorId);
        }
        
        // Destroy existing instance if any
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove(editorId);
        }
        
        // Initialize TinyMCE
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            try {
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'lists,link,wordpress,wplink,paste',
                        toolbar1: 'bold,italic,link,unlink,bullist,numlist',
                        toolbar2: '',
                        forced_root_block: 'p',
                        force_br_newlines: false,
                        force_p_newlines: true,
                        height: 120,
                        init_instance_callback: function(editor) {
                            console.log('FAQ answer editor initialized for index:', index);
                        },
                        setup: function(editor) {
                            editor.on('change keyup', function() {
                                editor.save();
                                // Update the repeater field data
                                var value = editor.getContent();
                                $textarea.val(value).trigger('change');
                            });
                        }
                    },
                    quicktags: {
                        buttons: 'strong,em,link'
                    },
                    mediaButtons: false
                });
            } catch (e) {
                console.error('Error initializing FAQ answer editor:', e);
            }
        }
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
                    forced_root_block: 'p',  // Forces Enter to create <p> tags
                    force_br_newlines: false, // Enter creates paragraphs, not <br>
                    force_p_newlines: true,   // Forces paragraph tags
                    remove_linebreaks: false, // Don't strip line breaks
                    convert_newlines_to_brs: false, // Don't convert to <br> tags
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
                    toolbar1: 'formatselect,bold,italic,link,unlink',
                    toolbar2: '',
                    format_tags: 'p',  // Only allow paragraph tags for outro
                    forced_root_block: 'p',  // Forces Enter to create <p> tags
                    force_br_newlines: false, // Enter creates paragraphs, not <br>
                    force_p_newlines: true,   // Forces paragraph tags
                    remove_linebreaks: false, // Don't strip line breaks
                    convert_newlines_to_brs: false, // Don't convert to <br> tags
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
     * Initialize stats repeater for Stats sections
     */
    function initStatsRepeater(content) {
        var $container = $('#stats-items');
        if (!$container.length) {
            return; // Container not found
        }
        
        // Get stats from content or empty array
        var stats = content.stats || [];
        
        // Initialize repeater field
        var statsRepeater = $container.aisbRepeaterField({
            fieldName: 'stats',
            items: stats,
            defaultItem: {
                number: '100',
                label: 'Stat Label',
                description: 'Optional description'
            },
            maxItems: 8,
            minItems: 0,
            itemLabel: 'Stat',
            addButtonText: 'Add Stat',
            template: function(item, index) {
                return `
                    <div class="aisb-repeater-fields">
                        <div class="aisb-repeater-field-group">
                            <label>Number/Value</label>
                            <input type="text" class="aisb-repeater-field" data-field="number" 
                                   value="${escapeHtml(item.number || '')}" 
                                   placeholder="e.g. 99%, 50M+, 24/7">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Label</label>
                            <input type="text" class="aisb-repeater-field" data-field="label" 
                                   value="${escapeHtml(item.label || '')}" 
                                   placeholder="What this stat represents">
                        </div>
                        <div class="aisb-repeater-field-group">
                            <label>Description (Optional)</label>
                            <input type="text" class="aisb-repeater-field" data-field="description" 
                                   value="${escapeHtml(item.description || '')}" 
                                   placeholder="Additional context">
                        </div>
                    </div>
                `;
            },
            onUpdate: function(items) {
                // Update the section content with stats data
                if (editorState.currentSection !== null && editorState.sections[editorState.currentSection]) {
                    editorState.sections[editorState.currentSection].content.stats = items;
                    editorState.isDirty = true;
                    
                    // Re-render the preview section
                    var section = editorState.sections[editorState.currentSection];
                    var sectionHtml = renderSection(section, editorState.currentSection);
                    $('.aisb-section[data-index="' + editorState.currentSection + '"]').replaceWith(sectionHtml);
                    
                    // Update save status
                    updateSaveStatus('unsaved');
                }
            }
        });
        
        // Store repeater instance
        editorState.statsRepeater = statsRepeater;
    }
    
    /**
     * Initialize WYSIWYG editors for Features section
     */
    function initFeaturesWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('features-content');
            wp.editor.remove('features-outro-content');
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('features-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',  // Forces Enter to create <p> tags
                    force_br_newlines: false, // Enter creates paragraphs, not <br>
                    force_p_newlines: true,   // Forces paragraph tags
                    remove_linebreaks: false, // Don't strip line breaks
                    convert_newlines_to_brs: false, // Don't convert to <br> tags
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
            wp.editor.initialize('features-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,link,unlink',
                    toolbar2: '',
                    format_tags: 'p',  // Only allow paragraph tags for outro
                    forced_root_block: 'p',  // Forces Enter to create <p> tags
                    force_br_newlines: false, // Enter creates paragraphs, not <br>
                    force_p_newlines: true,   // Forces paragraph tags
                    remove_linebreaks: false, // Don't strip line breaks
                    convert_newlines_to_brs: false, // Don't convert to <br> tags
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
            console.warn('WordPress editor not available for Features section, falling back to textarea');
        }
    }
    
    /**
     * Initialize checklist WYSIWYG editors
     */
    function initChecklistWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('checklist-content');
            wp.editor.remove('checklist-outro-content');
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('checklist-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
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
                    buttons: 'strong,em,link'
                },
                mediaButtons: false
            });
            
            // Outro content editor
            wp.editor.initialize('checklist-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'bold,italic,link,unlink',
                    toolbar2: '',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    paste_as_text: false,
                    paste_remove_styles: true,
                    paste_remove_styles_if_webkit: true,
                    paste_strip_class_attributes: 'all',
                    height: 150,
                    setup: function(editor) {
                        editor.on('change keyup', function() {
                            editor.save();
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
            console.warn('WordPress editor not available for Checklist section, falling back to textarea');
        }
    }
    
    /**
     * Initialize WYSIWYG editors for FAQ section
     */
    function initFaqWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('faq-content');
            wp.editor.remove('faq-outro-content');
            
            // Also remove any FAQ answer editors
            for (var i = 0; i < 20; i++) {
                wp.editor.remove('faq-answer-' + i);
            }
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('faq-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
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
                    buttons: 'strong,em,link'
                },
                mediaButtons: false
            });
            
            // Outro content editor
            wp.editor.initialize('faq-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'bold,italic,link,unlink',
                    toolbar2: '',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    paste_as_text: false,
                    paste_remove_styles: true,
                    paste_remove_styles_if_webkit: true,
                    paste_strip_class_attributes: 'all',
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
            console.warn('WordPress editor not available for FAQ section, falling back to textarea');
        }
    }
    
    /**
     * Initialize WYSIWYG editors for Stats section
     */
    function initStatsWysiwygEditors() {
        // Destroy existing instances first (if any)
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.remove('stats-content');
            wp.editor.remove('stats-outro-content');
        }
        
        // Initialize TinyMCE for content fields
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            // Main content editor
            wp.editor.initialize('stats-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    paste_as_text: false,
                    paste_remove_styles: true,
                    paste_remove_styles_if_webkit: true,
                    paste_strip_class_attributes: 'all',
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
            
            // Outro content editor
            wp.editor.initialize('stats-outro-content', {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,paste',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink',
                    toolbar2: '',
                    format_tags: 'p;h2;h3;h4',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true,
                    remove_linebreaks: false,
                    convert_newlines_to_brs: false,
                    paste_as_text: false,
                    paste_remove_styles: true,
                    paste_remove_styles_if_webkit: true,
                    paste_strip_class_attributes: 'all',
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
            console.warn('WordPress editor not available for Stats section, falling back to textarea');
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
                    
                    // Debug logging to track media state
                    debugLog('Layout variant changed', {
                        sectionType: editorState.sections[editorState.currentSection].type,
                        newLayout: variantValue,
                        mediaType: content.media_type,
                        hasImage: !!content.featured_image,
                        hasVideo: !!content.video_url
                    });
                    
                    // Note: Media fields are now visible for ALL layouts including center
                    // This matches hero and features section behavior
                } else if (variantType === 'card_alignment') {
                    content.card_alignment = variantValue;
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
                    
                    // Close the modal after successful selection
                    mediaFrame.close();
                }
            });
            
            // Fix aria-hidden focus warning when modal is ready
            mediaFrame.on('ready', function() {
                // Remove aria-hidden from uploader parent and modal elements to prevent focus warning
                $('[id^="__wp-uploader-id-"]').removeAttr('aria-hidden');
                $('.media-modal').removeAttr('aria-hidden');
                $('.media-modal-backdrop').removeAttr('aria-hidden');
                
                debugLog('Media Modal Ready', {
                    uploaderElements: $('[id^="__wp-uploader-id-"]').length,
                    modalElements: $('.media-modal').length
                });
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
        
        // Handle form type switching for hero-form
        // This ONLY toggles form input visibility and updates the preview form area
        $(document).on('change', 'input[name="form_type"]', function() {
            var formType = $(this).val();
            var $selector = $(this).closest('.aisb-form-selector');
            
            // Toggle form input visibility in editor panel
            $selector.find('.aisb-form-shortcode-controls').hide();
            if (formType === 'shortcode') {
                $selector.find('.aisb-form-shortcode-controls').show();
            }
            
            // Update state and preview form area ONLY
            if (editorState.currentSection !== null && editorState.sections[editorState.currentSection]) {
                var section = editorState.sections[editorState.currentSection];
                if (section.content) {
                    // Update form type in state
                    section.content.form_type = formType;
                    editorState.isDirty = true;
                    
                    // Update the preview to reflect the form type change
                    updatePreview();
                    
                    updateSaveStatus('unsaved');
                }
            }
        });
        
        // Handle form shortcode input changes
        // Debounced to prevent rapid updates
        var shortcodeTimer;
        $(document).on('input', 'textarea[name="form_shortcode"]', function() {
            var shortcode = $(this).val();
            
            // Clear previous timer
            clearTimeout(shortcodeTimer);
            
            // Debounce the update
            shortcodeTimer = setTimeout(function() {
                if (editorState.currentSection !== null) {
                    var section = editorState.sections[editorState.currentSection];
                    if (section && section.content) {
                        section.content.form_shortcode = shortcode;
                        editorState.isDirty = true;
                        
                        // Update the preview
                        updatePreview();
                        updateSaveStatus('unsaved');
                    }
                }
            }, 500); // Wait 500ms after user stops typing
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
        
        // Card image selection
        $(document).on('click', '.select-card-image', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var cardId = $button.data('card-id');
            var $cardItem = $button.closest('.aisb-repeater-item');
            
            // Create a new media frame for cards
            var cardMediaFrame = wp.media({
                title: 'Select Card Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected
            cardMediaFrame.on('select', function() {
                var attachment = cardMediaFrame.state().get('selection').first().toJSON();
                
                // Update the hidden input value
                $cardItem.find('input[data-field="image"]').val(attachment.url).trigger('change');
                
                // Update the preview
                var $mediaSelector = $button.closest('.aisb-card-media-selector');
                var $preview = $mediaSelector.find('.aisb-card-media-preview');
                
                if ($preview.length) {
                    // Update existing preview
                    $preview.find('img').attr('src', attachment.url);
                } else {
                    // Create new preview
                    var previewHtml = `
                        <div class="aisb-card-media-preview">
                            <img src="${attachment.url}" alt="Card image">
                            <button type="button" class="aisb-card-media-remove" data-card-id="${cardId}">
                                <span class="dashicons dashicons-no-alt"></span> Remove
                            </button>
                        </div>
                    `;
                    $button.before(previewHtml);
                }
                
                // Update button text
                $button.find('span:last').text('Change Image');
            });
            
            // Open the media frame
            cardMediaFrame.open();
        });
        
        // Card image removal
        $(document).on('click', '.aisb-card-media-remove', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $cardItem = $button.closest('.aisb-repeater-item');
            
            // Clear the image value
            $cardItem.find('input[data-field="image"]').val('').trigger('change');
            
            // Remove the preview
            $button.closest('.aisb-card-media-preview').remove();
            
            // Update button text
            $cardItem.find('.select-card-image span:last').text('Select Image');
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
            wp.editor.remove('features-content');
            wp.editor.remove('features-outro-content');
            wp.editor.remove('checklist-content');
            wp.editor.remove('checklist-outro-content');
            wp.editor.remove('faq-content');
            wp.editor.remove('faq-outro-content');
            
            // Remove any FAQ answer editors
            for (var i = 0; i < 20; i++) {
                wp.editor.remove('faq-answer-' + i);
            }
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
            content[field.name] = field.value;
        });
        
        // Get the current section first
        var currentSection = editorState.sections[editorState.currentSection];
        
        // Debug: Check hero heading specifically
        if (currentSection && currentSection.type === 'hero') {
            debugLog('Hero Heading Check', {
                formHeading: content.heading,
                currentHeading: currentSection.content ? currentSection.content.heading : 'no content',
                formDataRaw: formData.filter(f => f.name === 'heading')
            });
        }
        
        // Debug: Check if items are in form data
        debugLog('updatePreview - Checking for items in form data', {
            hasItemsInFormData: formData.some(field => field.name === 'items'),
            formDataKeys: formData.map(field => field.name)
        });
        
        // Get TinyMCE content directly if editors exist
        // This ensures we get the HTML content, not the raw textarea value
        if (currentSection && currentSection.type) {
            var sectionType = currentSection.type;
            if (sectionType === 'hero') {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('hero-content')) {
                    content.content = tinyMCE.get('hero-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('hero-outro-content')) {
                    content.outro_content = tinyMCE.get('hero-outro-content').getContent();
                }
            } else if (sectionType === 'hero-form') {
                // Hero-form uses same editor IDs as hero
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('hero-content')) {
                    content.content = tinyMCE.get('hero-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('hero-outro-content')) {
                    content.outro_content = tinyMCE.get('hero-outro-content').getContent();
                }
            } else if (sectionType === 'features') {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('features-content')) {
                    content.content = tinyMCE.get('features-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('features-outro-content')) {
                    content.outro_content = tinyMCE.get('features-outro-content').getContent();
                }
            } else if (sectionType === 'checklist') {
                // Debug checklist media state BEFORE update
                debugLog('Checklist updatePreview - Before', {
                    formMediaType: content.media_type,
                    formImage: content.featured_image,
                    formVideo: content.video_url,
                    currentMediaType: currentSection.content.media_type,
                    currentImage: currentSection.content.featured_image,
                    currentVideo: currentSection.content.video_url,
                    layoutVariant: content.layout_variant
                });
                
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('checklist-content')) {
                    content.content = tinyMCE.get('checklist-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('checklist-outro-content')) {
                    content.outro_content = tinyMCE.get('checklist-outro-content').getContent();
                }
            } else if (sectionType === 'faq') {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-content')) {
                    content.content = tinyMCE.get('faq-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('faq-outro-content')) {
                    content.outro_content = tinyMCE.get('faq-outro-content').getContent();
                }
            } else if (sectionType === 'stats') {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('stats-content')) {
                    content.content = tinyMCE.get('stats-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('stats-outro-content')) {
                    content.outro_content = tinyMCE.get('stats-outro-content').getContent();
                }
            } else if (sectionType === 'testimonials') {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('testimonials-content')) {
                    content.content = tinyMCE.get('testimonials-content').getContent();
                }
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('testimonials-outro-content')) {
                    content.outro_content = tinyMCE.get('testimonials-outro-content').getContent();
                }
            }
        }
        
        // Debug content fields specifically
        // console.log('[DEBUG] updatePreview - Content fields:', {
        //     'content': content.content,
        //     'outro_content': content.outro_content,
        //     'hasContentP': content.content ? content.content.includes('<p>') : false,
        //     'hasOutroP': content.outro_content ? content.outro_content.includes('<p>') : false
        // });
        
        debugLog('updatePreview - Content from Form', content);
        
        // IMPORTANT: Preserve complex data that's managed outside the form
        // Note: currentSection is already defined above
        if (currentSection && currentSection.content) {
            // Preserve global blocks managed by repeater
            if (currentSection.content.global_blocks) {
                content.global_blocks = currentSection.content.global_blocks;
            }
            
            // Preserve cards managed by repeater
            if (currentSection.content.cards) {
                content.cards = currentSection.content.cards;
            }
            
            // Preserve checklist items managed by repeater
            if (currentSection.content.items) {
                content.items = currentSection.content.items;
                debugLog('Preserving checklist items in updatePreview', {
                    items: currentSection.content.items,
                    itemCount: currentSection.content.items.length
                });
            }
            
            // Preserve FAQ items managed by repeater (handle both field names)
            if (currentSection.content.questions) {
                content.questions = currentSection.content.questions;
                content.faq_items = currentSection.content.questions; // Keep both for compatibility
                debugLog('Preserving FAQ questions in updatePreview', {
                    questions: currentSection.content.questions,
                    itemCount: currentSection.content.questions.length
                });
            } else if (currentSection.content.faq_items) {
                content.faq_items = currentSection.content.faq_items;
                content.questions = currentSection.content.faq_items; // Keep both for compatibility
                debugLog('Preserving FAQ items in updatePreview', {
                    faq_items: currentSection.content.faq_items,
                    itemCount: currentSection.content.faq_items.length
                });
            }
            
            // Preserve stats items managed by repeater
            if (currentSection.content.stats) {
                content.stats = currentSection.content.stats;
                debugLog('Preserving stats items in updatePreview', {
                    stats: currentSection.content.stats,
                    itemCount: currentSection.content.stats ? currentSection.content.stats.length : 0
                });
            }
            
            // Preserve testimonials managed by repeater
            if (currentSection.content.testimonials) {
                content.testimonials = currentSection.content.testimonials;
                debugLog('Preserving testimonials in updatePreview', {
                    testimonials: currentSection.content.testimonials,
                    itemCount: currentSection.content.testimonials ? currentSection.content.testimonials.length : 0
                });
            }
            
            // Preserve variant data managed by toggle buttons
            if (currentSection.content.theme_variant) {
                content.theme_variant = currentSection.content.theme_variant;
            }
            if (currentSection.content.layout_variant) {
                content.layout_variant = currentSection.content.layout_variant;
            }
            if (currentSection.content.card_alignment) {
                content.card_alignment = currentSection.content.card_alignment;
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
            
            // Debug checklist media state AFTER update
            if (editorState.sections[editorState.currentSection].type === 'checklist') {
                debugLog('Checklist updatePreview - After Update', {
                    savedMediaType: content.media_type,
                    savedImage: content.featured_image,
                    savedVideo: content.video_url,
                    layoutVariant: content.layout_variant,
                    fullContent: content
                });
            }
            
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
            
            // Trigger event for accordion and other frontend scripts
            $(document).trigger('aisb:preview:updated');
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
            
            // Trigger event for accordion and other frontend scripts
            $(document).trigger('aisb:preview:updated');
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
        } else if (section.type === 'hero-form') {
            return renderHeroFormSection(section, index);
        } else if (section.type === 'features') {
            return renderFeaturesSection(section, index);
        } else if (section.type === 'checklist') {
            return renderChecklistSection(section, index);
        } else if (section.type === 'faq') {
            return renderFaqSection(section, index);
        } else if (section.type === 'stats') {
            return renderStatsSection(section, index);
        } else if (section.type === 'testimonials') {
            return renderTestimonialsSection(section, index);
        }
        return '';
    }
    
    /**
     * Render media preview based on type and content
     */
    function renderMediaPreview(content, sectionType) {
        var mediaType = content.media_type || 'none';
        var imageUrl = content.featured_image || '';
        var videoUrl = content.video_url || '';
        
        // Determine media class based on section type (default to hero for backward compatibility)
        var mediaClass = sectionType === 'features' ? 'aisb-features__media' : 
                        sectionType === 'checklist' ? 'aisb-checklist__media' : 
                        sectionType === 'faq' ? 'aisb-faq__media' :
                        sectionType === 'stats' ? 'aisb-stats__media' :
                        sectionType === 'testimonials' ? 'aisb-testimonials__media' :
                        'aisb-hero__media';
        
        debugLog('renderMediaPreview Called', {
            mediaType: mediaType,
            imageUrl: imageUrl,
            videoUrl: videoUrl,
            sectionType: sectionType,
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
                    <div class="${mediaClass}">
                        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(content.heading || '')}" />
                    </div>
                `;
            } else {
                debugLog('renderMediaPreview - Showing Image Placeholder', 'No image URL');
                // Show placeholder with SVG
                return `
                    <div class="${mediaClass}">
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
                    var videoClass = sectionType === 'features' ? 'aisb-features__video' : 
                                    sectionType === 'checklist' ? 'aisb-checklist__video' :
                                    sectionType === 'stats' ? 'aisb-stats__video' :
                                    sectionType === 'testimonials' ? 'aisb-testimonials__video' :
                                    'aisb-hero__video';
                    return `
                        <div class="${mediaClass}">
                            <iframe class="${videoClass}" 
                                    src="https://www.youtube-nocookie.com/embed/${youtubeMatch[1]}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    `;
                } else {
                    // Self-hosted video
                    var videoClass = sectionType === 'features' ? 'aisb-features__video' : 
                                    sectionType === 'checklist' ? 'aisb-checklist__video' :
                                    sectionType === 'stats' ? 'aisb-stats__video' :
                                    sectionType === 'testimonials' ? 'aisb-testimonials__video' :
                                    'aisb-hero__video';
                    return `
                        <div class="${mediaClass}">
                            <video class="${videoClass}" controls>
                                <source src="${escapeHtml(videoUrl)}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    `;
                }
            } else {
                // Show placeholder with SVG
                return `
                    <div class="${mediaClass}">
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
     * Render form placeholder for hero-form section
     */
    function renderFormPlaceholder() {
        return `
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
        `;
    }
    
    /**
     * Execute scripts within HTML content
     * This is necessary for forms that include JavaScript
     * Follows the pattern used by WordPress core and form plugins
     */
    function executeScripts(container) {
        // Find all script elements
        var scripts = container.querySelectorAll('script');
        
        scripts.forEach(function(oldScript) {
            // Create a new script element
            var newScript = document.createElement('script');
            
            // Copy attributes
            Array.from(oldScript.attributes).forEach(function(attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            
            // Copy content if inline script
            if (!oldScript.src) {
                newScript.textContent = oldScript.textContent;
            }
            
            // Replace old script with new one to execute it
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }
    
    /**
     * Update ONLY the form area without affecting other preview elements
     * This prevents form changes from corrupting button or other UI data
     */
    
    /**
     * Render form area for hero-form section
     * IMPORTANT: This function should ONLY update the form display area
     * It should NOT affect any other editor elements or trigger preview updates
     */
    function renderFormArea(content) {
        var formType = content.form_type || 'placeholder';
        var formId = 'form-container-' + Date.now();
        
        // Validate and sanitize shortcode input
        if (formType === 'shortcode' && content.form_shortcode && content.form_shortcode.trim() !== '') {
            // Only make AJAX call if we have actual content
            // Use a longer delay to prevent conflicts with other updates
            setTimeout(function() {
                // Check if the container still exists (user might have switched sections)
                var container = document.getElementById(formId);
                if (!container) return;
                
                $.ajax({
                    url: aisbEditor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'aisb_render_form',
                        nonce: aisbEditor.nonce,
                        form_type: formType,
                        form_shortcode: content.form_shortcode
                    },
                    success: function(response) {
                        // Only update the specific form container, not the entire preview
                        var targetContainer = document.getElementById(formId);
                        if (targetContainer && response.success && response.data.html) {
                            targetContainer.innerHTML = response.data.html;
                            // Execute any scripts in the rendered form
                            if (response.data.has_scripts) {
                                executeScripts(targetContainer);
                            }
                        }
                    },
                    error: function() {
                        console.warn('AISB: Failed to render form shortcode');
                    }
                });
            }, 200); // Increased delay to avoid race conditions
            
            // Return placeholder with ID for AJAX to update
            return `<div id="${formId}" class="aisb-form-container">${renderFormPlaceholder()}</div>`;
            
        } else {
            // Show placeholder for empty or placeholder type
            return renderFormPlaceholder();
        }
    }
    
    /**
     * Render global blocks (buttons, cards, etc.)
     */
    function renderGlobalBlocks(blocks, sectionType = 'hero') {
        console.log('=== renderGlobalBlocks DEBUG ===');
        console.log('1. Blocks received:', blocks);
        console.log('2. Section type:', sectionType);
        
        // Parse blocks if it's a JSON string
        if (typeof blocks === 'string') {
            try {
                blocks = JSON.parse(blocks);
            } catch (e) {
                console.error('Failed to parse global_blocks:', e);
                blocks = [];
            }
        }
        
        if (!blocks || !blocks.length) {
            console.log('3. No blocks to render, returning empty');
            return '';
        }
        
        var html = '';
        var buttons = blocks.filter(function(block) { return block.type === 'button'; });
        
        console.log('4. Buttons filtered:', buttons);
        
        // Render buttons if any
        if (buttons.length) {
            var buttonHtml = buttons.map(function(button, idx) {
                console.log(`5. Button ${idx} text:`, button.text);
                if (!button.text) {
                    console.log(`6. Button ${idx} has no text, skipping`);
                    return '';
                }
                var styleClass = 'aisb-btn-' + (button.style || 'primary');
                
                // In preview, render as button tags (not links) to avoid styling issues
                return `<button class="aisb-btn ${styleClass}" type="button">${escapeHtml(button.text)}</button>`;
            }).join('');
            
            console.log('7. Button HTML generated:', buttonHtml);
            
            if (buttonHtml) {
                // Use correct container class based on section type
                const containerClass = sectionType === 'features' ? 'aisb-features__buttons' :
                                      sectionType === 'checklist' ? 'aisb-checklist__buttons' :
                                      sectionType === 'faq' ? 'aisb-faq__buttons' :
                                      sectionType === 'stats' ? 'aisb-stats__buttons' :
                                      sectionType === 'testimonials' ? 'aisb-testimonials__buttons' :
                                      sectionType === 'hero-form' ? 'aisb-hero-form__buttons' :
                                      'aisb-hero__buttons';
                html += `<div class="${containerClass}">${buttonHtml}</div>`;
            }
        }
        
        console.log('8. Final HTML:', html);
        console.log('=== END renderGlobalBlocks DEBUG ===');
        
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
                                ${renderGlobalBlocks(content.global_blocks, 'hero')}
                                ${content.outro_content ? `<div class="aisb-hero__outro">${content.outro_content}</div>` : ''}
                            </div>
                            ${renderMediaPreview(content, 'hero')}
                        </div>
                    </div>
                </section>
            </div>
        `;
    }
    
    /**
     * Render Hero Form section - Exactly like Hero but without media
     */
    function renderHeroFormSection(section, index) {
        console.log('=== renderHeroFormSection CALLED ===');
        console.log('1. Section data:', JSON.parse(JSON.stringify(section)));
        console.log('2. Index:', index);
        
        var content = migrateOldFieldNames(section.content || section);
        
        console.log('3. Content after migration:', JSON.parse(JSON.stringify(content)));
        console.log('4. Global blocks being rendered:', JSON.parse(JSON.stringify(content.global_blocks)));
        
        // Build class list based on variants
        var sectionClasses = [
            'aisb-section',
            'aisb-section-hero-form',
            'aisb-section--' + (content.theme_variant || 'dark'),
            'aisb-section--' + (content.layout_variant || 'content-left')
        ].join(' ');
        
        var buttonsHtml = renderGlobalBlocks(content.global_blocks, 'hero-form');
        console.log('5. Buttons HTML generated:', buttonsHtml);
        
        return `
            <div class="${sectionClasses}" data-index="${index}">
                <section class="aisb-hero-form">
                    <div class="aisb-hero-form__container">
                        <div class="aisb-hero-form__grid">
                            <div class="aisb-hero-form__content">
                                ${content.eyebrow_heading ? `<div class="aisb-hero-form__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                                <h1 class="aisb-hero-form__heading">${escapeHtml(content.heading || 'Your Headline Here')}</h1>
                                <div class="aisb-hero-form__body">${content.content || '<p>Your compelling message goes here</p>'}</div>
                                ${buttonsHtml}
                                ${content.outro_content ? `<div class="aisb-hero-form__outro">${content.outro_content}</div>` : ''}
                            </div>
                            <div class="aisb-hero-form__form">
                                ${renderFormArea(content)}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        `;
    }
    
    /**
     * Render Features section preview
     */
    function renderFeaturesSection(section, index) {
        var content = section.content || section;
        
        // Debug: Log the raw content to see what we're getting
        // console.log('[DEBUG] Features content field:', {
        //     raw: content.content,
        //     hasHtmlTags: content.content ? content.content.includes('<p>') : false,
        //     length: content.content ? content.content.length : 0
        // });
        
        // Build class list based on variants - must match PHP structure EXACTLY
        var sectionClasses = [
            'aisb-section',
            'aisb-features',  // CRITICAL: Must be combined with aisb-section
            'aisb-section--' + (content.theme_variant || 'light'),
            'aisb-section--' + (content.layout_variant || 'content-left'),
            'aisb-features--cards-' + (content.card_alignment || 'left')
        ].join(' ');
        
        return `
            <section class="${sectionClasses}" data-index="${index}">
                <div class="aisb-features__container">
                        <!-- Top section with content and optional media (like Hero) -->
                        <div class="aisb-features__top">
                            <div class="aisb-features__content">
                                ${content.eyebrow_heading ? `<div class="aisb-features__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                                <h2 class="aisb-features__heading">${escapeHtml(content.heading || 'Our Features')}</h2>
                                <div class="aisb-features__intro">${content.content || '<p>Discover what makes us different</p>'}</div>
                            </div>
                            ${renderMediaPreview(content, 'features')}
                        </div>
                        
                        <!-- Feature Cards -->
                        ${renderFeatureCards(content.cards)}
                        
                        <!-- Buttons -->
                        ${renderGlobalBlocks(content.global_blocks, 'features')}
                        ${content.outro_content ? `<div class="aisb-features__outro">${content.outro_content}</div>` : ''}
                    </div>
            </section>
        `;
    }
    
    /**
     * Render Checklist section preview
     */
    function renderChecklistSection(section, index) {
        var content = section.content || section;
        
        debugLog('renderChecklistSection called', {
            section: section,
            content: content,
            items: content.items,
            itemsLength: content.items ? content.items.length : 0
        });
        
        // Build class list based on variants
        var sectionClasses = [
            'aisb-section',
            'aisb-checklist',
            'aisb-section--' + (content.theme_variant || 'light'),
            'aisb-section--' + (content.layout_variant || 'content-left')
        ].join(' ');
        
        // Render checklist items
        var itemsHtml = renderChecklistItems(content.items);
        
        // Build the section HTML based on layout
        var sectionContent = '';
        
        if (content.layout_variant === 'center') {
            // Center layout - single column, with media below content (matching hero/features)
            sectionContent = `
                <div class="aisb-checklist__center">
                    ${content.eyebrow_heading ? `<div class="aisb-checklist__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                    ${content.heading ? `<h2 class="aisb-checklist__heading">${escapeHtml(content.heading)}</h2>` : ''}
                    ${content.content ? `<div class="aisb-checklist__content">${content.content}</div>` : ''}
                    ${itemsHtml}
                    ${renderGlobalBlocks(content.global_blocks, 'checklist')}
                    ${content.outro_content ? `<div class="aisb-checklist__outro">${content.outro_content}</div>` : ''}
                    ${renderMediaPreview(content, 'checklist')}
                </div>
            `;
        } else {
            // Two-column layout
            var contentColumn = `
                <div class="aisb-checklist__content-column">
                    ${content.eyebrow_heading ? `<div class="aisb-checklist__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                    ${content.heading ? `<h2 class="aisb-checklist__heading">${escapeHtml(content.heading)}</h2>` : ''}
                    ${content.content ? `<div class="aisb-checklist__content">${content.content}</div>` : ''}
                    ${itemsHtml}
                    ${renderGlobalBlocks(content.global_blocks, 'checklist')}
                    ${content.outro_content ? `<div class="aisb-checklist__outro">${content.outro_content}</div>` : ''}
                </div>
            `;
            
            var mediaColumn = '';
            if (content.media_type !== 'none') {
                mediaColumn = `
                    <div class="aisb-checklist__media-column">
                        ${renderMediaPreview(content, 'checklist')}
                    </div>
                `;
            }
            
            sectionContent = `
                <div class="aisb-checklist__columns">
                    ${contentColumn}
                    ${mediaColumn}
                </div>
            `;
        }
        
        return `
            <section class="${sectionClasses}" data-index="${index}">
                <div class="aisb-checklist__container">
                    ${sectionContent}
                </div>
            </section>
        `;
    }
    
    /**
     * Render FAQ Section for preview
     */
    function renderFaqSection(section, index) {
        var content = section.content || section;
        
        // Handle both 'questions' (from AI) and 'faq_items' (legacy) field names
        var faqItems = content.questions || content.faq_items || [];
        
        debugLog('renderFaqSection called', {
            section: section,
            content: content,
            questions: content.questions,
            faq_items: content.faq_items,
            finalItems: faqItems,
            itemsLength: faqItems.length
        });
        
        // Build class list based on variants
        var sectionClasses = [
            'aisb-section',
            'aisb-faq',
            'aisb-section--' + (content.theme_variant || 'light'),
            'aisb-section--' + (content.layout_variant || 'center')
        ].join(' ');
        
        // Render FAQ items with accordion structure
        var itemsHtml = '';
        if (faqItems && faqItems.length > 0) {
            itemsHtml = '<div class="aisb-faq__items">';
            faqItems.forEach(function(item, itemIndex) {
                if (item.question || item.answer) {
                    itemsHtml += '<div class="aisb-faq__item" data-faq-index="' + itemIndex + '">';
                    if (item.question) {
                        itemsHtml += '<h3 class="aisb-faq__item-question" data-faq-toggle="' + itemIndex + '">' + escapeHtml(item.question) + '</h3>';
                    }
                    if (item.answer) {
                        itemsHtml += '<div class="aisb-faq__item-answer" data-faq-content="' + itemIndex + '">';
                        itemsHtml += '<div class="aisb-faq__item-answer-inner">' + item.answer + '</div>';
                        itemsHtml += '</div>';
                    }
                    itemsHtml += '</div>';
                }
            });
            itemsHtml += '</div>';
        }
        
        // Build the section HTML based on layout
        var sectionContent = '';
        
        if (content.layout_variant === 'center') {
            // Center layout - single column, with media below content
            sectionContent = `
                <div class="aisb-faq__center">
                    ${content.eyebrow_heading ? `<div class="aisb-faq__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                    ${content.heading ? `<h2 class="aisb-faq__heading">${escapeHtml(content.heading)}</h2>` : ''}
                    ${content.content ? `<div class="aisb-faq__content">${content.content}</div>` : ''}
                    ${itemsHtml}
                    ${renderGlobalBlocks(content.global_blocks, 'faq')}
                    ${content.outro_content ? `<div class="aisb-faq__outro">${content.outro_content}</div>` : ''}
                    ${renderMediaPreview(content, 'faq')}
                </div>
            `;
        } else {
            // Two-column layout
            var contentColumn = `
                <div class="aisb-faq__content-column">
                    ${content.eyebrow_heading ? `<div class="aisb-faq__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                    ${content.heading ? `<h2 class="aisb-faq__heading">${escapeHtml(content.heading)}</h2>` : ''}
                    ${content.content ? `<div class="aisb-faq__content">${content.content}</div>` : ''}
                    ${itemsHtml}
                    ${renderGlobalBlocks(content.global_blocks, 'faq')}
                    ${content.outro_content ? `<div class="aisb-faq__outro">${content.outro_content}</div>` : ''}
                </div>
            `;
            
            var mediaColumn = '';
            if (content.media_type !== 'none') {
                mediaColumn = `
                    <div class="aisb-faq__media-column">
                        ${renderMediaPreview(content, 'faq')}
                    </div>
                `;
            }
            
            sectionContent = `
                <div class="aisb-faq__columns">
                    ${contentColumn}
                    ${mediaColumn}
                </div>
            `;
        }
        
        return `
            <section class="${sectionClasses}" data-index="${index}">
                <div class="aisb-faq__container">
                    ${sectionContent}
                </div>
            </section>
        `;
    }
    
    /**
     * Render Stats section preview - Phase 1: Core structure
     */
    function renderStatsSection(section, index) {
        var content = section.content || section;
        
        // Build section classes matching PHP implementation
        var themeVariant = content.theme_variant || 'light';
        var layoutVariant = content.layout_variant || 'center';
        
        var sectionClasses = [
            'aisb-section',
            'aisb-stats',
            'aisb-section--' + themeVariant,
            'aisb-section--' + layoutVariant
        ].join(' ');
        
        return `
            <section class="${sectionClasses}" data-index="${index}">
                <div class="aisb-stats__container">
                    <!-- Top section with content and media (using shared renderMediaPreview like Features) -->
                    <div class="aisb-stats__top">
                        <div class="aisb-stats__content">
                            ${content.eyebrow_heading ? `<div class="aisb-stats__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                            <h2 class="aisb-stats__heading">${escapeHtml(content.heading || 'By the Numbers')}</h2>
                            <div class="aisb-stats__intro">${content.content || '<p>Our impact and achievements</p>'}</div>
                        </div>
                        ${renderMediaPreview(content, 'stats')}
                    </div>
                    
                    <!-- Stats Items Grid -->
                    ${renderStatItems(content.stats)}
                    
                    <!-- Buttons -->
                    ${renderGlobalBlocks(content.global_blocks, 'stats')}
                    
                    <!-- Outro Content -->
                    ${content.outro_content ? `<div class="aisb-stats__outro">${content.outro_content}</div>` : ''}
                </div>
            </section>
        `;
    }
    
    /**
     * Render Testimonials section in preview
     */
    function renderTestimonialsSection(section, index) {
        var content = section.content || section;
        
        // Build section classes matching PHP implementation
        var themeVariant = content.theme_variant || 'light';
        var layoutVariant = content.layout_variant || 'center';
        
        var sectionClasses = [
            'aisb-section',
            'aisb-testimonials',
            'aisb-section--' + themeVariant,
            'aisb-section--' + layoutVariant
        ].join(' ');
        
        return `
            <section class="${sectionClasses}" data-index="${index}">
                <div class="aisb-testimonials__container">
                    <!-- Top section with content and media -->
                    <div class="aisb-testimonials__top">
                        <div class="aisb-testimonials__content">
                            ${content.eyebrow_heading ? `<div class="aisb-testimonials__eyebrow">${escapeHtml(content.eyebrow_heading)}</div>` : ''}
                            <h2 class="aisb-testimonials__heading">${escapeHtml(content.heading || 'What Our Customers Say')}</h2>
                            <div class="aisb-testimonials__intro">${content.content || '<p>Hear from real people who have achieved amazing results with our solution.</p>'}</div>
                        </div>
                        ${renderMediaPreview(content, 'testimonials')}
                    </div>
                    
                    <!-- Testimonials Grid -->
                    ${renderTestimonialItems(content.testimonials)}
                    
                    <!-- Buttons -->
                    ${renderGlobalBlocks(content.global_blocks, 'testimonials')}
                    
                    <!-- Outro Content -->
                    ${content.outro_content ? `<div class="aisb-testimonials__outro">${content.outro_content}</div>` : ''}
                </div>
            </section>
        `;
    }
    
    /**
     * Update save status indicator - Modernized version
     */
    function updateSaveStatus(status) {
        var $saveBtn = $('#aisb-save-sections');
        
        // Only proceed if this is the main sections save button
        if (!$saveBtn.length || $saveBtn.attr('id') !== 'aisb-save-sections') {
            return;
        }
        
        var $btnText = $saveBtn.find('.aisb-save-btn-text');
        var $btnIcon = $saveBtn.find('.dashicons');
        
        // Create button inner elements if they don't exist - only for our button
        if (!$btnText.length && $saveBtn.attr('id') === 'aisb-save-sections') {
            // Preserve the original text if it exists
            var originalText = $saveBtn.text().trim();
            $saveBtn.html('<span class="dashicons dashicons-saved"></span><span class="aisb-save-btn-text">' + (originalText || 'Save') + '</span>');
            $btnText = $saveBtn.find('.aisb-save-btn-text');
            $btnIcon = $saveBtn.find('.dashicons');
        }
        
        // Reset classes
        $saveBtn.removeClass('has-changes is-saving is-saved');
        
        // Ensure button is enabled for states that allow saving
        if (status !== 'saving') {
            $saveBtn.prop('disabled', false);
        }
        
        switch (status) {
            case 'unsaved':
                $saveBtn.addClass('has-changes');
                $btnIcon.removeClass().addClass('dashicons dashicons-upload');
                // Check if global settings are also dirty
                if (editorState.globalSettingsDirty) {
                    $btnText.text('Save All Changes');
                } else {
                    $btnText.text('Save Changes');
                }
                // Schedule auto-save
                scheduleAutoSave();
                break;
            case 'saving':
                editorState.isSaving = true;
                $saveBtn.prop('disabled', true).addClass('is-saving');
                $btnIcon.removeClass().addClass('dashicons dashicons-update aisb-spin');
                $btnText.text('Saving...');
                break;
            case 'saved':
                editorState.isSaving = false;
                $saveBtn.addClass('is-saved');
                $btnIcon.removeClass().addClass('dashicons dashicons-yes-alt');
                $btnText.text('Saved');
                // Revert to default state after 2 seconds
                setTimeout(function() {
                    if (!editorState.isDirty) {
                        $btnIcon.removeClass().addClass('dashicons dashicons-saved');
                        $btnText.text('Save');
                        $saveBtn.removeClass('is-saved');
                    }
                }, 2000);
                break;
            case 'error':
                editorState.isSaving = false;
                $btnIcon.removeClass().addClass('dashicons dashicons-warning');
                $btnText.text('Save Failed');
                // Revert to unsaved state after 3 seconds
                setTimeout(function() {
                    if (editorState.isDirty) {
                        updateSaveStatus('unsaved');
                    }
                }, 3000);
                break;
            default:
                $btnIcon.removeClass().addClass('dashicons dashicons-saved');
                $btnText.text('Save');
        }
    }
    
    // Expose updateSaveStatus globally for integration
    window.updateSaveStatus = updateSaveStatus;
    
    /**
     * Save sections (manual save button or auto-save)
     */
    function saveSections(isAutoSave) {
        // Clear any pending auto-save
        if (editorState.autoSaveTimer) {
            clearTimeout(editorState.autoSaveTimer);
            editorState.autoSaveTimer = null;
        }
        
        // Check what needs saving
        var needsSectionSave = editorState.isDirty;
        var needsGlobalSave = editorState.globalSettingsDirty && window.aisbGlobalSettings && window.aisbGlobalSettings.hasUnsavedChanges;
        
        // Don't save if already saving or no changes
        if (editorState.isSaving || (!needsSectionSave && !needsGlobalSave)) {
            return;
        }
        
        // Perform save
        updateSaveStatus('saving');
        
        // Prepare promises for all saves needed
        var savePromises = [];
        
        // Save sections if needed
        if (needsSectionSave) {
            savePromises.push(saveSectionsToServer());
        }
        
        // Save global settings if needed
        if (needsGlobalSave && window.aisbGlobalSettings) {
            savePromises.push(saveGlobalSettingsFromMain());
        }
        
        // Execute all saves
        Promise.all(savePromises)
            .then(function() {
                editorState.isDirty = false;
                editorState.globalSettingsDirty = false;
                editorState.lastSaved = new Date();
                updateSaveStatus('saved');
                
                // Different notification for auto-save vs manual
                if (isAutoSave) {
                    debugLog('Auto-saved successfully');
                } else {
                    var message = needsGlobalSave && needsSectionSave ? 'All changes saved' : 'Changes saved';
                    showNotification(message, 'success');
                }
            })
            .catch(function(error) {
                updateSaveStatus('error');
                showNotification('Save failed: ' + error.message, 'error');
                
                // Re-schedule auto-save on failure
                if (isAutoSave) {
                    scheduleAutoSave();
                }
            });
    }
    
    /**
     * Save global settings from main save button
     */
    function saveGlobalSettingsFromMain() {
        return new Promise(function(resolve, reject) {
            if (!window.aisbGlobalSettings || !window.aisbGlobalSettings.savePrimaryColor) {
                resolve(); // No global settings to save
                return;
            }
            
            // Get all color values
            var primaryColor = $('#aisb-gs-primary').val();
            var baseLightColor = $('#aisb-gs-base-light').val() || '#ffffff';
            var baseDarkColor = $('#aisb-gs-base-dark').val() || '#1a1a1a';
            var textLightColor = $('#aisb-gs-text-light').val() || '#1a1a1a';
            var textDarkColor = $('#aisb-gs-text-dark').val() || '#fafafa';
            var mutedLightColor = $('#aisb-gs-muted-light').val() || '#64748b';
            var mutedDarkColor = $('#aisb-gs-muted-dark').val() || '#9ca3af';
            var secondaryLightColor = $('#aisb-gs-secondary-light').val() || '#f1f5f9';
            var secondaryDarkColor = $('#aisb-gs-secondary-dark').val() || '#374151';
            var borderLightColor = $('#aisb-gs-border-light').val() || '#e2e8f0';
            var borderDarkColor = $('#aisb-gs-border-dark').val() || '#4b5563';
            
            // Save via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aisb_save_all_colors',
                    primary_color: primaryColor,
                    base_light_color: baseLightColor,
                    base_dark_color: baseDarkColor,
                    text_light_color: textLightColor,
                    text_dark_color: textDarkColor,
                    muted_light_color: mutedLightColor,
                    muted_dark_color: mutedDarkColor,
                    secondary_light_color: secondaryLightColor,
                    secondary_dark_color: secondaryDarkColor,
                    border_light_color: borderLightColor,
                    border_dark_color: borderDarkColor,
                    nonce: window.aisbColorSettings?.nonce || $('#aisb-color-nonce').val() || ''
                },
                success: function(response) {
                    if (response.success) {
                        // Mark global settings as saved
                        if (window.aisbGlobalSettings) {
                            window.aisbGlobalSettings.markAsSaved();
                        }
                        resolve();
                    } else {
                        reject(new Error(response.data?.message || 'Failed to save global settings'));
                    }
                },
                error: function() {
                    reject(new Error('Network error saving global settings'));
                }
            });
        });
    }
    
    /**
     * Schedule auto-save after user stops making changes
     */
    function scheduleAutoSave() {
        // Clear existing timer
        if (editorState.autoSaveTimer) {
            clearTimeout(editorState.autoSaveTimer);
        }
        
        // Check if there are any unsaved changes (sections or global settings)
        var hasUnsavedChanges = editorState.isDirty || 
                                (editorState.globalSettingsDirty && window.aisbGlobalSettings && window.aisbGlobalSettings.hasUnsavedChanges);
        
        // Only schedule if there are unsaved changes
        if (hasUnsavedChanges && !editorState.isSaving) {
            editorState.autoSaveTimer = setTimeout(function() {
                debugLog('Auto-saving changes...');
                saveSections(true); // Pass true to indicate auto-save
            }, 5000); // 5 seconds after last change
        }
    }
    
    /**
     * Initialize save protection and keyboard shortcuts
     */
    function initSaveProtection() {
        // Beforeunload protection
        window.addEventListener('beforeunload', function(e) {
            // Check for any unsaved changes (sections or global settings)
            var hasUnsavedChanges = (editorState.isDirty || editorState.globalSettingsDirty) && !editorState.isSaving;
            
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = ''; // Chrome requires this
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Keyboard shortcut for save (Ctrl/Cmd + S)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                // Check for any unsaved changes
                var hasUnsavedChanges = editorState.isDirty || editorState.globalSettingsDirty;
                if (hasUnsavedChanges && !editorState.isSaving) {
                    saveSections();
                }
            }
        });
        
        debugLog('Save protection initialized');
    }
    
    /**
     * Clear all sections from the page
     */
    function clearAllSections() {
        // Confirm with user
        if (!confirm('Are you sure you want to remove all sections from this page? This will clear any corrupted sections as well.')) {
            return;
        }
        
        var postId = $('#aisb-post-id').val();
        var nonce = $('#aisb_editor_nonce').val();
        var $button = $('#aisb-clear-all-sections');
        
        // Disable button and show progress
        var originalText = $button.text();
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Clearing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aisb_clear_all_sections',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Clear editor state
                    editorState.sections = [];
                    editorState.isDirty = false;
                    editorState.currentSection = null;
                    
                    // Clear the preview
                    $('#aisb-sections-preview').html(
                        '<div class="aisb-editor-empty-state">' +
                        '<span class="dashicons dashicons-layout"></span>' +
                        '<h2>Start Building Your Page</h2>' +
                        '<p>Click a section type to add it to your page</p>' +
                        '</div>'
                    );
                    
                    // Clear the structure panel
                    $('#aisb-section-list').empty();
                    $('.aisb-structure-empty').show();
                    
                    // Now save the empty state to persist the changes
                    saveSectionsToServer().then(function() {
                        showNotification('All sections cleared and saved successfully', 'success');
                    }).catch(function(error) {
                        showNotification('Sections cleared but failed to save: ' + error, 'warning');
                    });
                    
                    // Reset button
                    $button.prop('disabled', false).text(originalText);
                } else {
                    showNotification('Failed to clear sections: ' + (response.data || 'Unknown error'), 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Clear sections error:', error);
                showNotification('Failed to clear sections. Please try again.', 'error');
                $button.prop('disabled', false).text(originalText);
            }
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
                beforeSend: function() {
                    // Debug: Log what we're saving for hero sections
                    editorState.sections.forEach(function(section, index) {
                        if (section.type === 'hero' || section.type === 'hero-form') {
                            console.log('Save Debug - ' + section.type + ' section ' + index + ':', {
                                heading: section.content ? section.content.heading : 'NO CONTENT',
                                fullContent: section.content,
                                fullSection: section
                            });
                        }
                    });
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
     * Update section list in right panel
     */
    function updateSectionList(skipSortableReinit) {
        console.log('=== updateSectionList CALLED ===');
        console.log('1. Skip sortable reinit:', skipSortableReinit);
        console.log('2. Current sections:', JSON.parse(JSON.stringify(editorState.sections)));
        
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
            var title = section.content.heading || section.content.headline || 'Untitled Section';
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
                        <span class="dashicons dashicons-menu" aria-hidden="true"></span>
                    </div>
                    <div class="aisb-section-item__icon" aria-hidden="true">
                        <span class="dashicons ${
                            section.type === 'features' ? 'dashicons-screenoptions' : 
                            section.type === 'stats' ? 'dashicons-chart-bar' :
                            section.type === 'faq' ? 'dashicons-editor-help' :
                            section.type === 'checklist' ? 'dashicons-yes-alt' :
                            'dashicons-megaphone'
                        }"></span>
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
        debugLog('Notification', type + ': ' + message);
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
        // console.log('=== MEDIA SYSTEM DEBUG ===');
        // console.log('Current Section Index:', editorState.currentSection);
        
        if (editorState.currentSection !== null) {
            var content = editorState.sections[editorState.currentSection].content;
            // console.log('Current Section Content:', content);
            // console.log('Media Type:', content.media_type);
            // console.log('Featured Image:', content.featured_image);
            // console.log('Video URL:', content.video_url);
            
            // Check what renderMediaPreview would return
            var preview = renderMediaPreview(content);
            // console.log('renderMediaPreview Output:', preview);
            
            // Check DOM
            var $mediaInPreview = $('.aisb-section[data-index="' + editorState.currentSection + '"] .aisb-hero__media');
            // console.log('Media element in preview exists:', $mediaInPreview.length > 0);
            if ($mediaInPreview.length > 0) {
                // console.log('Media element HTML:', $mediaInPreview[0].outerHTML);
            }
        } else {
            // console.log('No section currently selected');
        }
        
        // console.log('=== END DEBUG ===');
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
            debugLog('Initializing editor...');
            // console.log('Debug mode enabled. Use window.debugMediaSystem() in console to check media state.');
            initEditor();
            
            // Initialize autocomplete on any existing URL fields
            setTimeout(initializeUrlAutocomplete, 500);
            
            // Expose editor API for document upload integration
            window.aisbEditor = {
                /**
                 * Add a section to the editor
                 */
                addSection: function(section) {
                    if (!section || !section.type) {
                        console.error('Invalid section format');
                        return false;
                    }
                    
                    // Add to editor state
                    editorState.sections.push(section);
                    editorState.isDirty = true;
                    
                    // Render sections
                    renderSections();
                    updateSectionList();
                    updateSaveStatus('unsaved');
                    
                    return true;
                },
                
                /**
                 * Update section list display
                 */
                updateSectionList: function() {
                    updateSectionList();
                },
                
                /**
                 * Mark editor as having unsaved changes
                 */
                markDirty: function() {
                    editorState.isDirty = true;
                    updateSaveStatus('unsaved');
                },
                
                /**
                 * Get current sections
                 */
                getSections: function() {
                    return editorState.sections;
                },
                
                /**
                 * Clear all sections (with confirmation)
                 */
                clearSections: function(skipConfirmation) {
                    if (!skipConfirmation && !confirm('Are you sure you want to remove all sections?')) {
                        return false;
                    }
                    
                    editorState.sections = [];
                    editorState.isDirty = true;
                    renderSections();
                    updateSectionList();
                    updateSaveStatus('unsaved');
                    
                    return true;
                }
            };
            
            debugLog('Editor initialization complete');
        }
    });
    
})(jQuery);
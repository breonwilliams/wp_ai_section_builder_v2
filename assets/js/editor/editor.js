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
        sortableInstance: null
    };
    
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
        if (editorState.sortableInstance) {
            try {
                editorState.sortableInstance.destroy();
            } catch (error) {
                console.warn('AISB: Error destroying sortable instance:', error);
            }
        }
        
        // Reinitialize if drag-drop is enabled
        var features = (window.aisbEditor && window.aisbEditor.features) || {};
        if (features.dragDrop && typeof Sortable !== 'undefined') {
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
     * Hero section field defaults
     */
    var heroDefaults = {
        eyebrow: 'Welcome to the Future',
        headline: 'Your Headline Here',
        subheadline: 'Add your compelling message that engages visitors',
        button_text: 'Get Started',
        button_url: '#'
    };
    
    /**
     * Generate Hero section form
     */
    function generateHeroForm(content) {
        // Use existing content or defaults
        content = content || heroDefaults;
        
        return `
            <form id="aisb-section-form">
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-eyebrow">
                        Eyebrow Text
                    </label>
                    <input type="text" 
                           id="hero-eyebrow" 
                           name="eyebrow" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.eyebrow || '')}" 
                           placeholder="Welcome to the Future">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-headline">
                        Headline
                    </label>
                    <input type="text" 
                           id="hero-headline" 
                           name="headline" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.headline || '')}" 
                           placeholder="Enter your headline text">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-subheadline">
                        Subheadline
                    </label>
                    <textarea id="hero-subheadline" 
                              name="subheadline" 
                              class="aisb-editor-textarea" 
                              placeholder="Enter your subheadline or description">${escapeHtml(content.subheadline || '')}</textarea>
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-button-text">
                        Button Text
                    </label>
                    <input type="text" 
                           id="hero-button-text" 
                           name="button_text" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.button_text || '')}" 
                           placeholder="Get Started">
                </div>
                
                <div class="aisb-editor-form-group">
                    <label class="aisb-editor-form-label" for="hero-button-url">
                        Button URL
                    </label>
                    <input type="url" 
                           id="hero-button-url" 
                           name="button_url" 
                           class="aisb-editor-input" 
                           value="${escapeHtml(content.button_url || '')}" 
                           placeholder="https://">
                </div>
            </form>
        `;
    }
    
    /**
     * Bind form events
     */
    function bindFormEvents() {
        // Live preview on input
        $('#aisb-section-form input, #aisb-section-form textarea').on('input', function() {
            updatePreview();
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
        
        // Keyboard shortcut (Shift + S)
        $(document).on('keydown', function(e) {
            if (e.shiftKey && e.key === 'S') {
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
        $('#aisb-library-mode').hide();
        $('#aisb-edit-content').html(formHtml);
        $('#aisb-edit-mode').show();
    }
    
    /**
     * Show library mode in left panel
     */
    function showLibraryMode() {
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
        
        var formData = $('#aisb-section-form').serializeArray();
        var content = {};
        
        // Convert form data to object
        $.each(formData, function(i, field) {
            content[field.name] = field.value;
        });
        
        // Update section in state
        if (editorState.sections[editorState.currentSection]) {
            editorState.sections[editorState.currentSection].content = content;
            editorState.isDirty = true;
            
            // Re-render just this section
            var section = editorState.sections[editorState.currentSection];
            var sectionHtml = renderSection(section, editorState.currentSection);
            $('.aisb-section[data-index="' + editorState.currentSection + '"]').replaceWith(sectionHtml);
            
            // Update section list to show new title
            updateSectionList();
            
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
                var title = section.content.headline || 'Untitled Section';
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
     * Render Hero section
     */
    function renderHeroSection(section, index) {
        var content = section.content;
        
        return `
            <div class="aisb-section aisb-section-hero" data-index="${index}">
                <section class="aisb-hero">
                    <div class="aisb-hero__container">
                        <div class="aisb-hero__grid">
                            <div class="aisb-hero__content">
                                ${content.eyebrow ? `<div class="aisb-hero__eyebrow">${escapeHtml(content.eyebrow)}</div>` : ''}
                                <h1 class="aisb-hero__heading">${escapeHtml(content.headline || 'Your Headline Here')}</h1>
                                <p class="aisb-hero__body">${escapeHtml(content.subheadline || 'Your compelling message goes here')}</p>
                                ${content.button_text ? `
                                    <div class="aisb-hero__buttons">
                                        <button class="aisb-btn aisb-btn-primary">${escapeHtml(content.button_text)}</button>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="aisb-hero__media">
                                <div class="placeholder-media" style="aspect-ratio: 16/9; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                    Hero Media
                                </div>
                            </div>
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
        
        switch (status) {
            case 'unsaved':
                $saveBtn.addClass('has-changes');
                $statusIndicator.addClass('unsaved').text('Unsaved changes');
                break;
            case 'saving':
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
        
        // Disable button
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
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.data || 'Unknown server error'));
                    }
                },
                error: function(xhr, status, error) {
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
    function updateSectionList() {
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
        
        // Reinitialize Sortable.js after DOM changes
        reinitializeSortable();
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
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.aisb-editor-wrapper').length) {
            console.log('AISB: Initializing editor...');
            initEditor();
            console.log('AISB: Editor initialization complete');
        }
    });
    
})(jQuery);
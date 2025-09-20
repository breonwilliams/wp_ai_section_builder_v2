/**
 * Document Upload Handler
 * 
 * Handles document upload functionality in the editor
 * 
 * @package AI_Section_Builder
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        initDocumentUpload();
    });
    
    /**
     * Initialize document upload functionality
     */
    function initDocumentUpload() {
        const $uploadBtn = $('#aisb-upload-document');
        const $fileInput = $('#aisb-document-file');
        const $toolbar = $('.aisb-editor-toolbar__right');
        
        if (!$uploadBtn.length || !$fileInput.length) {
            console.warn('AISB: Upload elements not found');
            return;
        }
        
        // Handle upload button click
        $uploadBtn.on('click', function(e) {
            e.preventDefault();
            $fileInput.click();
        });
        
        // Handle file selection
        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file
            if (!validateFile(file)) {
                $fileInput.val(''); // Clear the input
                return;
            }
            
            // Upload the file
            uploadDocument(file);
        });
    }
    
    /**
     * Validate selected file
     */
    function validateFile(file) {
        // Check file type
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/msword', // .doc
            'text/plain' // .txt
        ];
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const allowedExtensions = ['docx', 'doc', 'txt'];
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
            showNotification('error', 'Please select a .docx, .doc, or .txt file');
            return false;
        }
        
        // Check file size (5MB limit)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            showNotification('error', 'File size must be less than 5MB');
            return false;
        }
        
        return true;
    }
    
    /**
     * Upload document to server
     */
    function uploadDocument(file) {
        // Get post ID from the file input data attribute
        const postId = $('#aisb-document-file').data('post-id');
        
        // Get the nonce from the hidden field
        const nonce = $('#aisb_editor_nonce').val();
        
        // Use WordPress global ajaxurl
        const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
        
        // Debug log
        console.log('Upload Debug - Nonce:', nonce);
        console.log('Upload Debug - URL:', ajaxUrl);
        console.log('Upload Debug - Post ID:', postId);
        
        const formData = new FormData();
        formData.append('action', 'aisb_upload_document');
        formData.append('nonce', nonce);
        formData.append('document', file);
        formData.append('post_id', postId);
        
        // Show progress
        showProgress('Uploading document...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        updateProgress('Uploading... ' + percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                hideProgress();
                
                // Debug log the response
                console.log('Upload response:', response);
                
                if (response && response.success) {
                    // Check if data exists
                    if (!response.data) {
                        showNotification('error', 'Invalid response format from server');
                        return;
                    }
                    
                    // Show success message
                    showNotification('success', response.data.message || 'Document uploaded successfully!');
                    
                    // Check if AI processing had an error
                    if (response.data.ai_error) {
                        showNotification('error', 'AI Processing Error: ' + response.data.error_message);
                    }
                    
                    // Show AI results if available (Phase 4)
                    if (response.data.ai_result) {
                        showAIResults(response.data.ai_result, response.data.phase_info);
                    } else if (response.data.extraction) {
                        // Show extraction details only (Phase 3 fallback)
                        var phaseInfo = response.data.phase_info || 'Text extraction successful. Configure AI settings to generate sections.';
                        showExtractionResults(response.data.extraction, phaseInfo);
                    }
                    
                    // Clear the file input for next upload
                    $('#aisb-document-file').val('');
                } else {
                    // Handle error response - check if data exists
                    var errorMessage = 'Upload failed. Please try again.';
                    if (response && response.data) {
                        if (response.data.message) {
                            errorMessage = response.data.message;
                        } else if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        }
                    }
                    showNotification('error', errorMessage);
                }
            },
            error: function(xhr, status, error) {
                hideProgress();
                console.error('Upload error:', error);
                showNotification('error', 'Upload failed: ' + error);
            }
        });
    }
    
    /**
     * Show progress indicator
     */
    function showProgress(message) {
        // Remove any existing progress
        hideProgress();
        
        // Create progress element
        const $progress = $('<div class="aisb-upload-progress">' +
            '<span class="spinner is-active"></span>' +
            '<span class="aisb-progress-text">' + message + '</span>' +
            '</div>');
        
        // Add after upload button
        $('#aisb-upload-document').after($progress);
    }
    
    /**
     * Update progress message
     */
    function updateProgress(message) {
        $('.aisb-progress-text').text(message);
    }
    
    /**
     * Hide progress indicator
     */
    function hideProgress() {
        $('.aisb-upload-progress').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Show extraction results (Phase 3)
     */
    function showExtractionResults(extraction, phaseInfo) {
        // Remove any existing results
        $('.aisb-extraction-results').remove();
        
        // Create results display
        const $results = $('<div class="aisb-extraction-results">' +
            '<h3>Document Extraction Results</h3>' +
            '<div class="aisb-extraction-stats">' +
            '<span><strong>Words:</strong> ' + extraction.stats.words + '</span>' +
            '<span><strong>Characters:</strong> ' + extraction.stats.characters + '</span>' +
            '<span><strong>Paragraphs:</strong> ' + extraction.stats.paragraphs + '</span>' +
            '</div>' +
            '<div class="aisb-extraction-preview">' +
            '<h4>Preview:</h4>' +
            '<p>' + escapeHtml(extraction.preview) + '</p>' +
            '</div>' +
            '<div class="aisb-phase-info">' +
            '<em>' + phaseInfo + '</em>' +
            '</div>' +
            '<button class="aisb-close-results" type="button">Close</button>' +
            '</div>');
        
        // Add to page
        $('.aisb-editor-toolbar').after($results);
        
        // Handle close
        $results.find('.aisb-close-results').on('click', function() {
            $results.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-hide after 15 seconds
        setTimeout(function() {
            $results.fadeOut(200, function() {
                $(this).remove();
            });
        }, 15000);
    }
    
    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Show AI processing results (Phase 4)
     */
    function showAIResults(aiResult, phaseInfo) {
        // Debug: Log what we received
        console.log('AI Result received:', aiResult);
        if (aiResult.sections) {
            console.log('Sections array:', aiResult.sections);
            console.log('First section:', aiResult.sections[0]);
        }
        if (aiResult.debug_raw) {
            console.log('Raw AI Response:', aiResult.debug_raw);
        }
        
        // Remove any existing results
        $('.aisb-ai-results').remove();
        
        // Add debug panel if in debug mode
        let debugHtml = '';
        if (window.aisbDebugMode || aiResult.debug_raw) {
            debugHtml = `
                <div class="aisb-debug-panel" style="background: #f0f0f0; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto;">
                    <h4 style="margin-top: 0;">Debug Information</h4>
                    <details>
                        <summary>Click to view raw AI response</summary>
                        <pre style="font-size: 11px; white-space: pre-wrap;">${JSON.stringify(aiResult.sections, null, 2)}</pre>
                    </details>
                </div>
            `;
        }
        
        // Build sections preview HTML
        let sectionsHtml = '';
        if (aiResult.sections && aiResult.sections.length > 0) {
            sectionsHtml = '<div class="aisb-sections-preview">';
            sectionsHtml += '<h4>Generated Sections:</h4>';
            sectionsHtml += '<ul class="aisb-sections-list">';
            
            aiResult.sections.forEach(function(section) {
                let sectionTitle = section.heading || section.type;
                let sectionType = section.type.charAt(0).toUpperCase() + section.type.slice(1).replace('-', ' ');
                sectionsHtml += '<li>';
                sectionsHtml += '<strong>' + sectionType + ':</strong> ';
                sectionsHtml += escapeHtml(sectionTitle);
                
                // Add item counts for certain section types
                if (section.features && section.features.length > 0) {
                    sectionsHtml += ' (' + section.features.length + ' features)';
                } else if (section.items && section.items.length > 0) {
                    sectionsHtml += ' (' + section.items.length + ' items)';
                } else if (section.faqs && section.faqs.length > 0) {
                    sectionsHtml += ' (' + section.faqs.length + ' FAQs)';
                } else if (section.stats && section.stats.length > 0) {
                    sectionsHtml += ' (' + section.stats.length + ' stats)';
                } else if (section.testimonials && section.testimonials.length > 0) {
                    sectionsHtml += ' (' + section.testimonials.length + ' testimonials)';
                }
                
                sectionsHtml += '</li>';
            });
            
            sectionsHtml += '</ul>';
            sectionsHtml += '</div>';
        }
        
        // Create results display
        const $results = $('<div class="aisb-ai-results">' +
            '<h3>AI Processing Complete</h3>' +
            debugHtml +
            '<div class="aisb-ai-summary">' +
            '<strong>Summary:</strong> ' + aiResult.summary +
            '</div>' +
            '<div class="aisb-ai-stats">' +
            '<span><strong>Sections Generated:</strong> ' + aiResult.sections_count + '</span>' +
            '</div>' +
            sectionsHtml +
            '<div class="aisb-phase-info">' +
            '<em>' + phaseInfo + '</em>' +
            '</div>' +
            '<div class="aisb-ai-actions">' +
            '<button class="aisb-add-sections" type="button">Add Sections to Editor</button>' +
            '<button class="aisb-close-results" type="button">Cancel</button>' +
            '</div>' +
            '</div>');
        
        // Add to page
        $('.aisb-editor-toolbar').after($results);
        
        // Handle add sections button
        $results.find('.aisb-add-sections').on('click', function() {
            // Phase 5: Add sections to editor
            addSectionsToEditor(aiResult.sections);
            $results.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Handle close
        $results.find('.aisb-close-results').on('click', function() {
            $results.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-hide after 30 seconds
        setTimeout(function() {
            $results.fadeOut(200, function() {
                $(this).remove();
            });
        }, 30000);
    }
    
    /**
     * Add sections to editor (Phase 5)
     */
    function addSectionsToEditor(sections) {
        console.log('addSectionsToEditor called with:', sections);
        
        if (!sections || sections.length === 0) {
            showNotification('error', 'No sections to add');
            return;
        }
        
        console.log('Processing', sections.length, 'sections');
        
        // Show progress
        showProgress('Adding sections to editor...');
        
        // Process each section
        let sectionsAdded = 0;
        sections.forEach(function(section, index) {
            setTimeout(function() {
                // Convert AI section to editor format
                const editorSection = convertAIToEditorSection(section);
                
                if (editorSection) {
                    // Normalize section data for consistent handling
                    const normalizedSection = window.AISBDataNormalizer ? 
                        window.AISBDataNormalizer.normalizeSection(editorSection) : 
                        editorSection;
                    
                    // Debug the normalization
                    if (window.AISBDataNormalizer && console.log) {
                        window.AISBDataNormalizer.debugDataTypes(normalizedSection, 'After AI conversion');
                    }
                    
                    // Add to editor state
                    if (window.aisbEditor && window.aisbEditor.addSection) {
                        window.aisbEditor.addSection(normalizedSection);
                        sectionsAdded++;
                    }
                }
                
                // Check if we're done
                if (index === sections.length - 1) {
                    hideProgress();
                    
                    if (sectionsAdded > 0) {
                        showNotification('success', sectionsAdded + ' sections added to editor successfully!');
                        
                        // Trigger update of section list
                        if (window.aisbEditor && window.aisbEditor.updateSectionList) {
                            window.aisbEditor.updateSectionList();
                        }
                        
                        // Mark editor as dirty (has unsaved changes)
                        if (window.aisbEditor && window.aisbEditor.markDirty) {
                            window.aisbEditor.markDirty();
                        }
                    } else {
                        showNotification('error', 'Failed to add sections to editor');
                    }
                }
            }, index * 100); // Small delay between sections for smooth UX
        });
    }
    
    /**
     * Convert AI-generated section to editor format
     */
    function convertAIToEditorSection(aiSection) {
        // Debug: Log what we're receiving from AI
        console.log('Converting AI section:', aiSection);
        
        // Verify content exists
        if (!aiSection.heading && !aiSection.description) {
            console.warn('Section missing critical content:', aiSection.type);
        }
        
        const section = {
            type: aiSection.type,
            content: {}
        };
        
        // Map AI fields to editor fields based on section type
        switch (aiSection.type) {
            case 'hero':
            case 'hero-form':
                // Log what we're converting
                console.log('Converting hero section:', {
                    heading: aiSection.heading,
                    description: aiSection.description,
                    buttons: aiSection.buttons
                });
                
                section.content = {
                    eyebrow_heading: aiSection.subheading || '',  // Map subheading to eyebrow
                    heading: aiSection.heading || 'Welcome',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>Welcome to our website</p>',  // Don't escape HTML
                    outro_content: '',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'content-left',
                    global_blocks: JSON.stringify(convertButtons(aiSection.buttons)), // Must be JSON string
                    form_mode: aiSection.type === 'hero-form' ? 'shortcode' : 'none',
                    form_shortcode: aiSection.type === 'hero-form' ? '[contact-form-7 id="YOUR_FORM_ID"]' : ''
                };
                break;
                
            case 'features':
                console.log('Processing features section:', aiSection);
                const featureCards = convertFeatureCards(aiSection.features);
                section.content = {
                    eyebrow_heading: aiSection.subheading || 'Features',
                    heading: aiSection.heading || 'Our Features',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>Discover what makes us unique</p>',
                    outro_content: '',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'content-left',
                    card_alignment: 'left',
                    cards: JSON.stringify(featureCards),  // Must be JSON string
                    global_blocks: JSON.stringify([])  // Empty array for now
                };
                console.log('Features section content created:', section.content);
                break;
                
            case 'checklist':
                console.log('Processing checklist section:', aiSection);
                const checklistItems = convertChecklistItems(aiSection.items);
                section.content = {
                    eyebrow_heading: aiSection.subheading || 'Why Choose Us',
                    heading: aiSection.heading || 'Benefits',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>Everything you need to succeed</p>',
                    outro_content: '',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'content-left',
                    items: JSON.stringify(checklistItems), // Must be JSON string
                    global_blocks: JSON.stringify([]) // Empty array
                };
                console.log('Checklist section content created:', section.content);
                break;
                
            case 'faq':
                console.log('Processing FAQ section:', aiSection);
                const faqItems = convertFAQs(aiSection.faqs);
                section.content = {
                    eyebrow_heading: aiSection.subheading || 'FAQs',
                    heading: aiSection.heading || 'Frequently Asked Questions',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>Find answers to common questions</p>',
                    outro_content: '<p>Still have questions? <a href="#contact">Contact us</a></p>',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'center',
                    questions: JSON.stringify(faqItems), // Must be JSON string - note: field is 'questions' not 'faqs'
                    global_blocks: JSON.stringify([]) // Empty array
                };
                console.log('FAQ section content created:', section.content);
                break;
                
            case 'stats':
                console.log('Processing stats section:', aiSection);
                const statItems = convertStats(aiSection.stats);
                section.content = {
                    eyebrow_heading: aiSection.subheading || 'Our Impact',
                    heading: aiSection.heading || 'By the Numbers',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>See our results</p>',
                    outro_content: '',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'center',
                    stats: JSON.stringify(statItems), // Must be JSON string
                    global_blocks: JSON.stringify([]) // Empty array
                };
                console.log('Stats section content created:', section.content);
                break;
                
            case 'testimonials':
                console.log('Processing testimonials section:', aiSection);
                const testimonialItems = convertTestimonials(aiSection.testimonials);
                section.content = {
                    eyebrow_heading: aiSection.subheading || 'Testimonials',
                    heading: aiSection.heading || 'What Our Clients Say',
                    content: aiSection.description ? '<p>' + aiSection.description + '</p>' : '<p>Hear from our satisfied customers</p>',
                    outro_content: '',
                    media_type: 'none',
                    featured_image: '',
                    video_url: '',
                    theme_variant: 'light',
                    layout_variant: 'center',
                    testimonials: JSON.stringify(testimonialItems), // Must be JSON string
                    global_blocks: JSON.stringify([]) // Empty array
                };
                console.log('Testimonials section content created:', section.content);
                break;
                
            default:
                console.warn('Unknown section type:', aiSection.type);
                return null;
        }
        
        return section;
    }
    
    /**
     * Convert AI buttons to editor format
     */
    function convertButtons(aiButtons) {
        if (!aiButtons || !Array.isArray(aiButtons)) {
            return [{
                type: 'button',  // CRITICAL: Must have type field
                text: 'Learn More',
                url: '#',
                style: 'primary'
            }];
        }
        
        return aiButtons.map(function(button) {
            return {
                type: 'button',  // CRITICAL: Must have type field
                text: button.text || 'Click Here',
                url: button.url || '#',
                style: button.style || 'primary'
            };
        });
    }
    
    /**
     * Convert AI features to editor feature cards format
     */
    function convertFeatureCards(aiFeatures) {
        if (!aiFeatures || !Array.isArray(aiFeatures)) {
            console.log('No features to convert or not an array:', aiFeatures);
            return [];
        }
        
        console.log('Converting ' + aiFeatures.length + ' features:', aiFeatures);
        
        const converted = aiFeatures.map(function(feature, index) {
            const card = {
                icon: feature.icon || 'dashicons-yes',
                heading: feature.title || feature.heading || '',
                content: feature.description || feature.content || '',
                link: '',
                link_text: 'Learn More',
                link_target: '_self',
                image: ''
            };
            console.log('Feature ' + index + ' converted:', card);
            return card;
        });
        
        console.log('All features converted:', converted);
        return converted;
    }
    
    /**
     * Convert AI checklist items to editor format
     */
    function convertChecklistItems(aiItems) {
        if (!aiItems || !Array.isArray(aiItems)) {
            console.log('No checklist items to convert or not an array:', aiItems);
            return [];
        }
        
        console.log('Converting ' + aiItems.length + ' checklist items:', aiItems);
        
        const converted = aiItems.map(function(item, index) {
            // Handle both string and object formats
            let result;
            if (typeof item === 'string') {
                result = {
                    heading: item,
                    content: ''
                };
            } else {
                result = {
                    heading: item.text || item.heading || item.title || '',
                    content: item.description || item.content || ''
                };
            }
            console.log('Checklist item ' + index + ' converted:', result);
            return result;
        });
        
        console.log('All checklist items converted:', converted);
        return converted;
    }
    
    /**
     * Convert AI FAQs to editor format
     */
    function convertFAQs(aiFAQs) {
        if (!aiFAQs || !Array.isArray(aiFAQs)) {
            console.log('No FAQs to convert or not an array:', aiFAQs);
            return [];
        }
        
        console.log('Converting ' + aiFAQs.length + ' FAQs:', aiFAQs);
        
        const converted = aiFAQs.map(function(faq, index) {
            const item = {
                question: faq.question || '',
                answer: faq.answer || ''
            };
            console.log('FAQ ' + index + ' converted:', item);
            return item;
        });
        
        console.log('All FAQs converted:', converted);
        return converted;
    }
    
    /**
     * Convert AI stats to editor format
     */
    function convertStats(aiStats) {
        if (!aiStats || !Array.isArray(aiStats)) {
            console.log('No stats to convert or not an array:', aiStats);
            return [];
        }
        
        console.log('Converting ' + aiStats.length + ' stats:', aiStats);
        
        const converted = aiStats.map(function(stat, index) {
            const item = {
                number: stat.number || stat.value || '0',
                label: stat.label || stat.title || '',
                description: stat.description || ''
            };
            console.log('Stat ' + index + ' converted:', item);
            return item;
        });
        
        console.log('All stats converted:', converted);
        return converted;
    }
    
    /**
     * Convert AI testimonials to editor format
     * PHP expects: content, author_name, author_title, author_image, rating
     */
    function convertTestimonials(aiTestimonials) {
        if (!aiTestimonials || !Array.isArray(aiTestimonials)) {
            console.log('No testimonials to convert or not an array:', aiTestimonials);
            return [];
        }
        
        console.log('Converting ' + aiTestimonials.length + ' testimonials:', aiTestimonials);
        
        const converted = aiTestimonials.map(function(testimonial, index) {
            // Map to the field names expected by PHP (class-testimonials-section.php lines 144-147)
            const item = {
                content: testimonial.quote || testimonial.content || testimonial.text || '',
                author_name: testimonial.author || testimonial.name || 'Anonymous',
                author_title: testimonial.role || testimonial.title || '',
                author_image: testimonial.image || testimonial.author_image || '',
                rating: testimonial.rating || 5
            };
            console.log('Testimonial ' + index + ' converted:', item);
            return item;
        });
        
        console.log('All testimonials converted:', converted);
        return converted;
    }
    
    /**
     * Show notification message
     */
    function showNotification(type, message) {
        // Remove any existing notifications
        $('.aisb-upload-notice').remove();
        
        // Create notification
        const iconClass = type === 'success' ? 'dashicons-yes' : 'dashicons-warning';
        const $notice = $('<div class="aisb-upload-notice aisb-upload-notice--' + type + '">' +
            '<span class="dashicons ' + iconClass + '"></span>' +
            '<span>' + message + '</span>' +
            '<button class="aisb-notice-dismiss" type="button">' +
            '<span class="dashicons dashicons-dismiss"></span>' +
            '</button>' +
            '</div>');
        
        // Add to page
        $('.aisb-editor-toolbar').after($notice);
        
        // Fade in
        $notice.hide().fadeIn(200);
        
        // Handle dismiss
        $notice.find('.aisb-notice-dismiss').on('click', function() {
            $notice.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
    
})(jQuery);